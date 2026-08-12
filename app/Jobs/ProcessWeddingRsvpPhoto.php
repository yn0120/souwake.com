<?php

namespace App\Jobs;

use App\Libraries\Utils;
use App\Models\WeddingRsvpPhotoModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * ゲストがアップロードした「お祝い画像」を、リサイズ・圧縮してstorageの保管領域へ移すジョブ。
 *
 * ゲストはスマートフォンから数MB〜十数MBの写真をそのまま投稿してくるため、
 * リクエスト内で変換すると待ち時間が長くPHPのタイムアウトにも掛かりやすい。
 * アップロード自体は一時領域への保存のみで即座に応答を返し、変換はHorizonの
 * weddingキュー（config/horizon.php の supervisor-wedding）で非同期に処理する。
 */
class ProcessWeddingRsvpPhoto implements ShouldQueue
{
    use Queueable;

    /** 長辺の上限（px）。閲覧・印刷に十分な範囲で保管サイズを抑える */
    private const MAX_IMAGE_DIMENSION = 2400;

    private const IMAGE_QUALITY = 85;

    /** @var array<string> 許可するMIMEタイプ */
    public const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];

    /** @var array<string> GD/Intervention Imageが直接デコードできず、事前にJPEGへ変換が必要な画像形式（iPhoneの標準形式） */
    private const HEIC_MIME = ['image/heic', 'image/heif'];

    public int $tries = 2;

    public function __construct(public readonly int $photoId)
    {
        $this->onQueue('wedding');
    }

    public function handle(): void
    {
        /** @var WeddingRsvpPhotoModel|null $photo */
        $photo = WeddingRsvpPhotoModel::getBy(['id' => $this->photoId, 'method' => 'first']);
        if (! $photo) {
            Utils::log('warning', "お祝い画像のDB行が見つからないため処理をスキップ ProcessWeddingRsvpPhoto#{$this->photoId}");

            return;
        }
        if ($photo->status === 'ready') {
            return;
        }

        $disk = Storage::disk('wedding_photos');
        $stagingPath = $photo->staging_path;
        $stagingFullPath = $stagingPath ? $disk->path($stagingPath) : null;
        $convertedPath = null;

        try {
            if (! $stagingFullPath || ! is_file($stagingFullPath)) {
                throw new \RuntimeException('アップロードされた一時ファイルが見つかりません。');
            }

            $photo->status = 'processing';
            $photo->save();

            $mimeType = self::detectMimeType($stagingFullPath);
            if (! in_array($mimeType, self::ALLOWED_MIME, true)) {
                throw new \RuntimeException("許可されていない画像形式です。（{$mimeType}）");
            }

            $sourcePath = $stagingFullPath;
            if (in_array($mimeType, self::HEIC_MIME, true)) {
                $convertedPath = self::convertHeicToJpeg($stagingFullPath);
                $sourcePath = $convertedPath;
                $mimeType = 'image/jpeg';
            }

            [$extension, $format] = self::resolveFormat($mimeType);
            $storedPath = 'photos/'.$photo->uuid.'.'.$extension;
            $destFullPath = $disk->path($storedPath);
            if (! is_dir(dirname($destFullPath))) {
                mkdir(dirname($destFullPath), 0755, true);
            }

            self::resizeAndSave($sourcePath, $destFullPath, $format);

            $photo->mime_type = $mimeType;
            $photo->size_bytes = filesize($destFullPath) ?: null;
            $photo->stored_path = $storedPath;
            $photo->staging_path = null;
            $photo->status = 'ready';
            $photo->save();
        } catch (\Throwable $e) {
            Utils::log('error', "お祝い画像の処理に失敗 ProcessWeddingRsvpPhoto#{$this->photoId}\n".$e->getMessage());

            // 最終試行で失敗した場合のみ失敗として確定させる（リトライ中は一時ファイルを残す）
            if ($this->attempts() >= $this->tries) {
                $photo->status = 'failed';
                $photo->staging_path = null;
                $photo->save();
                if ($stagingPath) {
                    $disk->delete($stagingPath);
                }
            }

            throw $e;
        } finally {
            if ($convertedPath && is_file($convertedPath)) {
                @unlink($convertedPath);
            }
        }

        if ($stagingPath) {
            $disk->delete($stagingPath);
        }
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime ?: 'application/octet-stream';
    }

    /**
     * 保存形式（拡張子, Intervention Imageのフォーマット名）を返す。
     *
     * @return array{0: string, 1: string}
     */
    private static function resolveFormat(string $mimeType): array
    {
        return match ($mimeType) {
            'image/png' => ['png', 'png'],
            'image/gif' => ['gif', 'gif'],
            'image/webp' => ['webp', 'webp'],
            default => ['jpg', 'jpg'],
        };
    }

    /**
     * 長辺が上限を超える場合のみ縮小して保存する（拡大はしない）。
     */
    private static function resizeAndSave(string $inputPath, string $outputPath, string $format): void
    {
        $manager = new ImageManager(new GdDriver);
        $image = $manager->read($inputPath);

        if ($image->width() > self::MAX_IMAGE_DIMENSION || $image->height() > self::MAX_IMAGE_DIMENSION) {
            $image->scaleDown(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION);
        }

        if ($format === 'png') {
            $image->save($outputPath, quality: 100, format: 'png');

            return;
        }

        if ($format === 'gif') {
            $image->save($outputPath, format: 'gif');

            return;
        }

        $image->save($outputPath, quality: self::IMAGE_QUALITY, format: $format);
    }

    /**
     * HEIC/HEIF（iPhoneの標準写真形式）はGD/Intervention Imageが直接デコードできないため、
     * libheif-examples（heif-convert）でJPEGへ変換してから既存の縮小処理に渡す。
     */
    private static function convertHeicToJpeg(string $inputPath): string
    {
        $outputPath = $inputPath.'.converted.jpg';
        $process = new Process([
            'heif-convert',
            '-q', (string) self::IMAGE_QUALITY,
            $inputPath,
            $outputPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if (is_file($outputPath)) {
            return $outputPath;
        }

        // 複数画像を持つHEICの場合、heif-convertは指定した出力パスではなく
        // `{basename}-1.jpg`, `{basename}-2.jpg`... の連番で書き出す。1枚目のみ採用する。
        $basename = preg_replace('/\.jpg$/', '', $outputPath);
        $numbered = glob($basename.'-*.jpg') ?: [];
        sort($numbered, SORT_NATURAL);

        if (empty($numbered)) {
            throw new \RuntimeException('HEIC画像の変換に失敗しました。');
        }

        $primary = array_shift($numbered);
        foreach ($numbered as $extra) {
            @unlink($extra);
        }

        return $primary;
    }
}
