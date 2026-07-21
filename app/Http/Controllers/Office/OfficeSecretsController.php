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
    private const PAGE_SIZE = 100;

    public function index(Request $request)
    {
        $records = SecretFileModel::where('status', 'ready')
            ->orderBy('original_name')
            ->limit(self::PAGE_SIZE + 1)
            ->get(['id', 'original_name', 'mime_type', 'created_at']);

        $hasMore = $records->count() > self::PAGE_SIZE;
        $records = $records->take(self::PAGE_SIZE);

        $assign = [
            'records' => self::toGalleryArray($records),
            'hasMore' => $hasMore,
        ];

        return view('office/secrets/index', compact('assign'));
    }

    public function list(Request $request)
    {
        $beforeId = (int) $request->query('before_id', 0);

        $query = SecretFileModel::where('status', 'ready')->orderByDesc('id');
        if ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }

        $records = $query->limit(self::PAGE_SIZE + 1)->get(['id', 'original_name', 'mime_type', 'created_at']);
        $hasMore = $records->count() > self::PAGE_SIZE;
        $records = $records->take(self::PAGE_SIZE);

        return response()->json([
            'records' => self::toGalleryArray($records),
            'has_more' => $hasMore,
        ]);
    }

    private static function toGalleryArray($records): array
    {
        return $records->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->original_name,
                'mime_type' => $r->mime_type,
                'created_at' => optional($r->created_at)->toDateTimeString(),
            ];
        })->values()->all();
    }

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
            Utils::log('error', "ファイルの鍵アンラップに失敗 OfficeSecretsController#view id={$file->id}");
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
                    Utils::log('error', "ファイル復号失敗のため配信を打ち切り uuid={$uuid} chunk={$i}");
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

    private function resolveRange(?string $rangeHeader, int $plainSize): array
    {
        if (! $rangeHeader || ! preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
            return [0, max(0, $plainSize - 1), false];
        }

        if ($m[1] === '' && $m[2] !== '') {
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
