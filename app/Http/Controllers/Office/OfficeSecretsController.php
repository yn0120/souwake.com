<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Models\SecretVaultKeyModel;
use App\Services\SecretFileCryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OfficeSecretsController extends Controller
{
    private const PAGE_SIZE = 100;

    public function index(Request $request)
    {
        $records = SecretFileModel::where('status', 'ready')
            ->orderByRaw("CAST(REGEXP_SUBSTR(original_name, '^[0-9]+') AS UNSIGNED) ASC")
            ->limit(self::PAGE_SIZE + 1)
            ->get(['id', 'original_name', 'mime_type', 'created_at']);

        $hasMore = $records->count() > self::PAGE_SIZE;
        $records = $records->take(self::PAGE_SIZE);

        $assign = [
            'records' => self::toGalleryArray($records),
            'hasMore' => $hasMore,
            // vault未登録なら、ブラウザ側でまず鍵ペアを作らせる必要がある
            'vaultRegistered' => SecretVaultKeyModel::query()->exists(),
        ];

        return view('office/secrets/index', compact('assign'));
    }

    public function list(Request $request)
    {
        $beforeName = $request->query('before_name', '');

        $query = SecretFileModel::where('status', 'ready')->orderByRaw("CAST(REGEXP_SUBSTR(original_name, '^[0-9]+') AS UNSIGNED) ASC");
        if ($beforeName !== '') {
            $query->whereRaw("CAST(REGEXP_SUBSTR(original_name, '^[0-9]+') AS UNSIGNED) > ?", [(int) $beforeName]);
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

    /**
     * 復号に必要なメタデータを返す。鍵そのものは「vault公開鍵でラップされた状態」でしか出さないため、
     * このレスポンスを傍受されてもファイルは復号できない（アンラップにはvault秘密鍵が要る）。
     *
     * ブラウザ側（secrets-sw.js）はこの情報だけで /secrets/raw/{id} の暗号文を復号できる。
     */
    public function meta(Request $request, $id)
    {
        $file = SecretFileModel::getBy(['id' => $id, 'status' => 'ready', 'method' => 'first']);
        if (! $file || ! $file->vault_wrapped_key) {
            abort(404);
        }

        return response()->json([
            'uuid' => $file->uuid,
            'mime_type' => $file->mime_type,
            'size_bytes' => (int) $file->size_bytes,
            'chunk_size' => SecretFileCryptoService::chunkSize(),
            'tag_len' => SecretFileCryptoService::tagLength(),
            'content_nonce_base' => $file->content_nonce_base,
            'eph_public_key' => $file->eph_public_key,
            'wrapped_key' => $file->vault_wrapped_key,
            'wrap_nonce' => $file->vault_wrap_nonce,
            'wrap_tag' => $file->vault_wrap_tag,
        ]);
    }

    /**
     * 暗号文をそのまま配信する。**サーバーは一切復号しない。**
     *
     * 実体は /var/encrypted 配下（ドキュメントルート外）にあるため、ここでは認可だけを行い、
     * X-Accel-Redirect でnginxの internal location に配信を委ねる。こうすることで
     *   - HTTP Range / 206 / 416 の処理がnginxのネイティブ実装になる（PHPで手実装する必要がない）
     *   - PHPのプロセスを掴んだままの長時間ストリーミングが無くなる
     *   - 出力バッファやzlibがレスポンス長を壊す余地が構造的に消える
     * という利点がある。
     *
     * 旧実装（response()->stream()でサーバー側復号）との違いは、エッジにも回線にも
     * 暗号文しか流れないこと。改ざん検知（GCMの認証失敗）もブラウザ側で行われる。
     */
    public function raw(Request $request, $id)
    {
        $file = SecretFileModel::getBy(['id' => $id, 'status' => 'ready', 'method' => 'first']);
        if (! $file) {
            abort(404);
        }

        $absolutePath = Storage::disk('secrets')->path($file->uuid);
        if (! is_file($absolutePath)) {
            Utils::log('error', "暗号化ファイルの実体が見つからない OfficeSecretsController#raw id={$file->id}");
            abort(404);
        }

        return response('', 200, [
            // prod.conf / local.conf の `location /__secrets_raw/ { internal; alias /var/encrypted/; }` に対応する
            'X-Accel-Redirect' => '/__secrets_raw/'.$file->uuid,
            // 中身は暗号文だが、内容の推測材料を与えないよう汎用のtypeにしておく
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
