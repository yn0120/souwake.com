<?php

namespace App\Jobs;

use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Services\SecretFileCryptoService;
use App\Services\SecureDeleteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * アップロードされたチャンクを再結合した平文ファイルを受け取り、
 * 画質を保ったまま圧縮 → チャンク単位AES-256-GCMで暗号化 → /var/encrypted へ保存するジョブ。
 *
 * コンストラクタにはDB行のid（int）のみを渡し、原ファイル名や平文を一切含めない
 * （Redisにジョブペイロードとして永続化されるため、機密情報を持ち込まない）。
 */
class ProcessSecretFileUpload implements ShouldQueue
{
    use Queueable;

    private const MAX_IMAGE_DIMENSION = 3840;

    private const IMAGE_QUALITY = 82;

    private const VIDEO_CRF = 23;

    /** @var array<string> 許可するMIMEタイプ */
    private const ALLOWED_IMAGE_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];

    /** @var array<string> GD/Intervention Imageが直接デコードできず、事前にJPEGへ変換が必要な画像形式 */
    private const HEIC_MIME = ['image/heic', 'image/heif'];

    private const ALLOWED_VIDEO_MIME = ['video/mp4', 'video/quicktime', 'video/webm'];

    public function __construct(public readonly int $secretFileId)
    {
        // 動画のffmpegトランスコードはCPU/メモリ負荷が高いため、専用の`secrets`キュー
        // （config/horizon.php の supervisor-secrets、同時実行数1）で処理する。
        $this->onQueue('secrets');
    }

    public function handle(): void
    {
        if (Cache::get('secrets:frozen')) {
            Utils::log('info', "ファイル抹消のフリーズ中のため処理を中断 ProcessSecretFileUpload#{$this->secretFileId}");

            return;
        }

        /** @var SecretFileModel|null $file */
        $file = SecretFileModel::getBy(['id' => $this->secretFileId, 'method' => 'first']);
        if (! $file) {
            Utils::log('warning', "ファイルのDB行が見つからないため処理をスキップ ProcessSecretFileUpload#{$this->secretFileId}（既に抹消済み、または作成に失敗した可能性）");

            return;
        }
        if ($file->status === 'ready') {
            Utils::log('info', "既に処理済みのためスキップ ProcessSecretFileUpload#{$this->secretFileId}");

            return;
        }

        $stagingPath = $file->staging_path;
        $compressedPath = null;

        try {
            if (! $stagingPath || ! is_file($stagingPath)) {
                throw new \RuntimeException('アップロード一時ファイルが見つかりません。');
            }

            $file->status = 'processing';
            $file->save();

            $mimeType = self::detectMimeType($stagingPath);
            $compressedPath = $stagingPath.'.compressed';

            if (in_array($mimeType, self::HEIC_MIME, true)) {
                $heicJpegPath = self::convertHeicToJpeg($stagingPath);
                try {
                    $mimeType = self::compressImage($heicJpegPath, $compressedPath, 'image/jpeg');
                } finally {
                    SecureDeleteService::wipeFile($heicJpegPath);
                }
            } elseif (in_array($mimeType, self::ALLOWED_IMAGE_MIME, true)) {
                $mimeType = self::compressImage($stagingPath, $compressedPath, $mimeType);
            } elseif (in_array($mimeType, self::ALLOWED_VIDEO_MIME, true)) {
                self::compressVideo($stagingPath, $compressedPath);
            } else {
                throw new \RuntimeException('許可されていないファイル形式です。');
            }

            self::encryptToStorage($file, $compressedPath, $mimeType);

            $file->status = 'ready';
            $file->staging_path = null;
            $file->save();
        } catch (\Throwable $e) {
            Utils::log('error', "ファイルの処理に失敗 ProcessSecretFileUpload#{$this->secretFileId}\n".$e->getMessage());
            $file->status = 'failed';
            $file->staging_path = null;
            $file->save();
        } finally {
            if ($stagingPath) {
                SecureDeleteService::wipeFile($stagingPath);
            }
            if ($compressedPath) {
                SecureDeleteService::wipeFile($compressedPath);
            }
        }
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);

        return $mime ?: 'application/octet-stream';
    }

    /**
     * 画質を保ったままサイズダウンして保存する。戻り値は保存後の実際のMIMEタイプ。
     */
    private static function compressImage(string $inputPath, string $outputPath, string $mimeType): string
    {
        $manager = new ImageManager(new GdDriver());
        $image = $manager->read($inputPath);

        if ($image->width() > self::MAX_IMAGE_DIMENSION || $image->height() > self::MAX_IMAGE_DIMENSION) {
            $image->scaleDown(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION);
        }

        if ($mimeType === 'image/png') {
            $image->save($outputPath, quality: 100, format: 'png');

            return 'image/png';
        }

        if ($mimeType === 'image/gif') {
            $image->save($outputPath, format: 'gif');

            return 'image/gif';
        }

        if ($mimeType === 'image/webp') {
            $image->save($outputPath, quality: self::IMAGE_QUALITY, format: 'webp');

            return 'image/webp';
        }

        $image->save($outputPath, quality: self::IMAGE_QUALITY, format: 'jpg');

        return 'image/jpeg';
    }

    /**
     * HEIC/HEIF（iPhoneの標準写真形式）はGD/Intervention Imageが直接デコードできないため、
     * libheif-examples（heif-convert）でJPEGへ変換してから既存の圧縮処理に渡す。
     */
    private static function convertHeicToJpeg(string $inputPath): string
    {
        $outputPath = $inputPath.'.heic-converted.jpg';
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

        // 複数画像（サムネイル等の補助画像を含む）を持つHEICの場合、heif-convertは
        // 指定した出力パスではなく `{basename}-1.jpg`, `{basename}-2.jpg`... の連番で書き出す。
        // 1枚目（プライマリ画像）のみを採用し、残りは平文のまま残さず消去する。
        $basename = preg_replace('/\.jpg$/', '', $outputPath);
        $numbered = glob($basename.'-*.jpg') ?: [];
        sort($numbered, SORT_NATURAL);

        if (empty($numbered)) {
            throw new \RuntimeException('HEIC画像の変換に失敗しました。');
        }

        $primary = array_shift($numbered);
        foreach ($numbered as $extra) {
            SecureDeleteService::wipeFile($extra);
        }

        return $primary;
    }

    private static function compressVideo(string $inputPath, string $outputPath): void
    {
        $process = new Process([
            'ffmpeg', '-y',
            '-i', $inputPath,
            '-c:v', 'libx264',
            '-crf', (string) self::VIDEO_CRF,
            '-preset', 'medium',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            // 出力パスに拡張子がない（ステージングファイル名に'.compressed'を付けているだけ）ため、
            // コンテナ形式をファイル名から推測できずffmpegが失敗する。明示的に指定する。
            '-f', 'mp4',
            $outputPath,
        ]);
        $process->setTimeout(1700);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * 圧縮済みファイルをチャンク単位AES-256-GCMで暗号化し、/var/encrypted（secretsディスク）へ書き込む。
     */
    private static function encryptToStorage(SecretFileModel $file, string $compressedPath, string $mimeType): void
    {
        $fileKey = SecretFileCryptoService::generateFileKey();
        $nonceBase = SecretFileCryptoService::generateContentNonceBase();
        $chunkSize = SecretFileCryptoService::chunkSize();
        $plainSize = filesize($compressedPath);

        $destPath = Storage::disk('secrets')->path($file->uuid);

        $in = fopen($compressedPath, 'rb');
        $out = fopen($destPath, 'wb');
        if ($in === false || $out === false) {
            throw new \RuntimeException('暗号化ファイルの書き込みに失敗しました。');
        }

        try {
            $totalChunks = (int) max(1, ceil($plainSize / $chunkSize));
            for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
                $plaintext = fread($in, $chunkSize);
                if ($plaintext === false) {
                    throw new \RuntimeException('暗号化対象ファイルの読み込みに失敗しました。');
                }
                $isLast = $chunkIndex === $totalChunks - 1;
                $encrypted = SecretFileCryptoService::encryptChunk($fileKey, $nonceBase, $chunkIndex, $isLast, $file->uuid, $plaintext);
                fwrite($out, $encrypted);
            }
            fflush($out);
            if (function_exists('fsync')) {
                fsync($out);
            }
        } finally {
            fclose($in);
            fclose($out);
        }

        $wrapped = SecretFileCryptoService::wrapFileKey($fileKey);

        $file->mime_type = $mimeType;
        $file->size_bytes = $plainSize;
        $file->wrapped_key = base64_encode($wrapped['wrapped_key']);
        $file->key_wrap_nonce = base64_encode($wrapped['nonce']);
        $file->key_wrap_tag = base64_encode($wrapped['tag']);
        $file->content_nonce_base = base64_encode($nonceBase);
        $file->save();
    }
}
