<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSecretFileUpload;
use App\Libraries\Utils;
use App\Models\SecretFileModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class OfficeSecretsUploadController extends Controller
{
    /** アップロード全体で許容する最大サイズ（バイト）。nginx/php側の上限と合わせている */
    private const MAX_TOTAL_SIZE = 3_200 * 1024 * 1024;

    public function input(Request $request)
    {
        $assign = [];

        return view('office/secrets/upload', compact('assign'));
    }

    /**
     * Dropzone.jsのチャンクアップロードプロトコル（dzuuid/dzchunkindex/dztotalchunkcount）を受ける。
     * 平文チャンクは/var/encryptedではなく secrets_tmp（通常ディスク）に再結合し、
     * 最終チャンク到着時にProcessSecretFileUploadジョブ（圧縮→暗号化）へ引き継ぐ。
     */
    public function chunk(Request $request)
    {
        if (Cache::get('secrets:frozen')) {
            return response()->json(['error' => '現在アップロードを受け付けていません。'], 503);
        }

        $uuid = (string) $request->input('dzuuid');
        $chunkIndex = (int) $request->input('dzchunkindex', 0);
        $totalChunks = (int) $request->input('dztotalchunkcount', 1);
        $totalFileSize = (int) $request->input('dztotalfilesize', 0);
        $uploadedFile = $request->file('file');

        if (! $uuid || ! preg_match('/^[a-zA-Z0-9\-]{1,64}$/', $uuid) || ! $uploadedFile) {
            return response()->json(['error' => '不正なリクエストです。'], 400);
        }

        if ($totalFileSize > self::MAX_TOTAL_SIZE) {
            return response()->json(['error' => 'ファイルサイズが上限を超えています。'], 413);
        }

        $stagingRoot = Storage::disk('secrets_tmp')->path('');
        if (! is_dir($stagingRoot)) {
            mkdir($stagingRoot, 0700, true);
        }
        $stagingPath = $stagingRoot.$uuid.'.upload';

        if ($chunkIndex === 0) {
            SecretFileModel::firstOrCreate(
                ['uuid' => $uuid],
                [
                    'admin_id' => Auth::id(),
                    'original_name' => mb_substr($uploadedFile->getClientOriginalName(), 0, 255),
                    'mime_type' => 'application/octet-stream',
                    'status' => 'uploading',
                    'staging_path' => $stagingPath,
                ],
            );
        }

        $in = fopen($uploadedFile->getRealPath(), 'rb');
        $out = fopen($stagingPath, $chunkIndex === 0 ? 'wb' : 'ab');
        if ($in === false || $out === false) {
            Utils::log('error', "秘密ファイルアップロードのチャンク書き込みに失敗 uuid={$uuid} chunk={$chunkIndex}");

            return response()->json(['error' => '書き込みに失敗しました。'], 500);
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        if ($chunkIndex >= $totalChunks - 1) {
            $file = SecretFileModel::getBy(['uuid' => $uuid, 'method' => 'first']);
            if ($file) {
                ProcessSecretFileUpload::dispatch($file->id);
            }
        }

        return response()->json(['success' => true]);
    }
}
