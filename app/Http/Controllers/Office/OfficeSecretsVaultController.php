<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Libraries\Utils;
use App\Models\SecretVaultKeyModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ファイル機能のE2E暗号化に使うvault鍵ペアの登録・取得を担うコントローラー。
 *
 * 重要な前提: **サーバーはvault秘密鍵の平文を一度も受け取らない。**
 * 鍵ペアの生成もラップもすべてブラウザ側（public/assets/js/secrets-vault.js）で行い、
 * ここへは「公開鍵」と「ラップ済みの秘密鍵」しか送られてこない。
 * したがってこのコントローラーが漏洩しても、攻撃者はファイルを復号できない。
 *
 * WebAuthnは認証手段ではなく**鍵導出手段**としてのみ使っている点に注意。
 * 認証はLaravelのセッション（RedirectIfNotAuthenticatedSecrets他）とCloudflare Accessが担うため、
 * ここでアテステーションの検証やチャレンジの管理は行わない（web-auth/webauthn-lib等の依存も不要）。
 * サーバーが保持するのは、次回のPRF評価に必要な credential_id と prf_salt だけ。
 */
class OfficeSecretsVaultController extends Controller
{
    /**
     * 登録済みのアンロック手段の一覧を返す。
     * ブラウザはこれを使ってWebAuthnのallowCredentialsを組み立て、PRFを評価して秘密鍵を復号する。
     */
    public function index(Request $request)
    {
        $rows = SecretVaultKeyModel::orderBy('id')->get();

        return response()->json([
            'registered' => $rows->isNotEmpty(),
            'public_key' => $rows->first()?->public_key,
            'keys' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'kind' => $r->kind,
                'label' => $r->label,
                'credential_id' => $r->credential_id,
                'prf_salt' => $r->prf_salt,
                'recovery_salt' => $r->recovery_salt,
                'wrapped_private_key' => $r->wrapped_private_key,
                'wrap_nonce' => $r->wrap_nonce,
                'wrap_tag' => $r->wrap_tag,
                'last_used_at' => optional($r->last_used_at)->toDateTimeString(),
            ])->values(),
        ]);
    }

    /**
     * アンロック手段を1つ登録する。
     *
     * 初回登録時はブラウザが新しい鍵ペアを生成して送ってくる。
     * 2件目以降（バックアップ認証器・リカバリコード）は、ブラウザが既存の秘密鍵を
     * 一度アンロックしたうえで別の鍵でラップし直して送ってくるため、public_keyは既存と一致する。
     * 一致しない場合は鍵ペアが分岐して既存ファイルが読めなくなるので拒否する。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kind' => 'required|in:webauthn,recovery',
            'label' => 'required|string|max:100',
            'credential_id' => 'nullable|string|max:512',
            'prf_salt' => 'nullable|string|max:64',
            'recovery_salt' => 'nullable|string|max:64',
            'public_key' => 'required|string|max:255',
            'wrapped_private_key' => 'required|string|max:512',
            'wrap_nonce' => 'required|string|max:64',
            'wrap_tag' => 'required|string|max:64',
        ]);

        if ($validated['kind'] === 'webauthn' && (! $validated['credential_id'] || ! $validated['prf_salt'])) {
            return response()->json(['error' => 'credential_idとprf_saltは必須です。'], 422);
        }
        if ($validated['kind'] === 'recovery' && ! $validated['recovery_salt']) {
            return response()->json(['error' => 'recovery_saltは必須です。'], 422);
        }

        $existingPublicKey = SecretVaultKeyModel::query()->value('public_key');
        if ($existingPublicKey && ! hash_equals($existingPublicKey, $validated['public_key'])) {
            // ここを通してしまうと、行ごとに違う鍵ペアが混在して「アップロード済みファイルを
            // 特定の認証器でしか開けない」状態になる。既存vaultを作り直したい場合は
            // 明示的に全行を削除してからやり直す運用にする。
            Utils::log('warning', 'vault公開鍵の不一致により登録を拒否 OfficeSecretsVaultController#store');

            return response()->json([
                'error' => '既存のvault鍵ペアと一致しません。既存の鍵をアンロックしてから登録してください。',
            ], 409);
        }

        $row = SecretVaultKeyModel::create($validated);

        Utils::log('info', "vaultアンロック手段を登録 kind={$row->kind} label={$row->label} id={$row->id}");

        return response()->json(['id' => $row->id], 201);
    }

    /**
     * アンロック手段を1つ削除する（認証器の紛失時など）。
     * 最後の1件は削除させない。全部消すとファイルが永久に開けなくなるため。
     */
    public function destroy(Request $request, $id)
    {
        if (SecretVaultKeyModel::count() <= 1) {
            return response()->json([
                'error' => '最後のアンロック手段は削除できません（全ファイルが復号不能になります）。',
            ], 409);
        }

        $deleted = SecretVaultKeyModel::where('id', $id)->delete();
        if (! $deleted) {
            abort(404);
        }

        Utils::log('info', "vaultアンロック手段を削除 id={$id}");

        return response()->json(['deleted' => true]);
    }

    /**
     * アンロック成功をサーバーへ記録する（どの手段が生きているかの把握用）。
     * 鍵に関する情報は一切受け取らない。
     */
    public function touch(Request $request, $id)
    {
        DB::table('secret_vault_keys')->where('id', $id)->update(['last_used_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
