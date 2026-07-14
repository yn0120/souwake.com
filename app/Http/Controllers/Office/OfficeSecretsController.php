<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Services\SecretFileCryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OfficeSecretsController extends Controller
{
    /**
     * 秘密ファイル一覧（Stage1は最小構成。無限スクロール/モーダル/ズーム等はStage3で追加）
     */
    public function index(Request $request)
    {
        $assign = [
            'records' => SecretFileModel::where('status', 'ready')->orderByDesc('id')->paginate(100),
        ];

        return view('office/secrets/index', compact('assign'));
    }

    /**
     * 秘密ファイルをチャンク単位で復号しながらストリーミング配信する。
     * 復号済みの平文をディスク/tmpfsに書き出すことは一切ない。HTTP Rangeに対応し、動画のシークも可能。
     */
    public function view(Request $request, $id)
    {
        $file = SecretFileModel::getBy(['id' => $id, 'status' => 'ready', 'method' => 'first']);
        if (! $file) {
            abort(404);
        }

        $absolutePath = Storage::disk('secrets')->path($file->uuid);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        try {
            $fileKey = SecretFileCryptoService::unwrapFileKey(
                base64_decode($file->wrapped_key),
                base64_decode($file->key_wrap_nonce),
                base64_decode($file->key_wrap_tag),
            );
        } catch (\Throwable $e) {
            Utils::log('error', "秘密ファイルの鍵アンラップに失敗 OfficeSecretsController#view id={$file->id}");
            abort(404);
        }

        $nonceBase = base64_decode($file->content_nonce_base);
        $chunkSize = SecretFileCryptoService::chunkSize();
        $tagLen = SecretFileCryptoService::tagLength();
        $plainSize = (int) $file->size_bytes;
        $totalChunks = (int) max(1, ceil($plainSize / $chunkSize));
        $diskFileSize = filesize($absolutePath);

        [$start, $end, $isPartial] = $this->resolveRange($request->header('Range'), $plainSize);
        if ($start === null) {
            return response('', 416, ['Content-Range' => "bytes */{$plainSize}"]);
        }

        $length = $end - $start + 1;
        $uuid = $file->uuid;

        $callback = function () use ($absolutePath, $fileKey, $nonceBase, $chunkSize, $tagLen, $totalChunks, $start, $end, $uuid, $diskFileSize) {
            $handle = fopen($absolutePath, 'rb');
            if ($handle === false) {
                return;
            }

            $chunkStartIndex = intdiv($start, $chunkSize);
            $chunkEndIndex = intdiv($end, $chunkSize);

            for ($i = $chunkStartIndex; $i <= $chunkEndIndex; $i++) {
                $onDiskOffset = $i * ($chunkSize + $tagLen);
                $isLast = $i === $totalChunks - 1;
                $blockSize = $isLast ? ($diskFileSize - $onDiskOffset) : ($chunkSize + $tagLen);

                fseek($handle, $onDiskOffset);
                $encrypted = fread($handle, $blockSize);

                try {
                    $plaintext = SecretFileCryptoService::decryptChunk($fileKey, $nonceBase, $i, $isLast, $uuid, $encrypted);
                } catch (\Throwable $e) {
                    // 改ざん・破損検知。機密情報を含めずログに残し、配信を即座に打ち切る（リトライ・フォールバックはしない）
                    Utils::log('error', "秘密ファイル復号失敗のため配信を打ち切り uuid={$uuid} chunk={$i}");
                    break;
                }

                $chunkPlainStart = $i * $chunkSize;
                $sliceStart = max(0, $start - $chunkPlainStart);
                $sliceEnd = min(strlen($plaintext) - 1, $end - $chunkPlainStart);

                if ($sliceStart <= $sliceEnd) {
                    echo substr($plaintext, $sliceStart, $sliceEnd - $sliceStart + 1);
                }
                flush();
            }

            fclose($handle);
        };

        $headers = [
            'Content-Type' => $file->mime_type,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ];
        if ($isPartial) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$plainSize}";
        }

        return response()->stream($callback, $isPartial ? 206 : 200, $headers);
    }

    /**
     * Rangeヘッダを解釈する。不正な範囲の場合は[null, null, false]を返す。
     *
     * @return array{0: int|null, 1: int|null, 2: bool}
     */
    private function resolveRange(?string $rangeHeader, int $plainSize): array
    {
        if (! $rangeHeader || ! preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
            return [0, max(0, $plainSize - 1), false];
        }

        if ($m[1] === '' && $m[2] !== '') {
            // サフィックス範囲（末尾Nバイト）
            $suffixLen = (int) $m[2];
            $start = max(0, $plainSize - $suffixLen);
            $end = $plainSize - 1;
        } else {
            $start = $m[1] === '' ? 0 : (int) $m[1];
            $end = $m[2] === '' ? $plainSize - 1 : (int) $m[2];
        }

        if ($start > $end || $end >= $plainSize || $start < 0) {
            return [null, null, false];
        }

        return [$start, $end, true];
    }
}
