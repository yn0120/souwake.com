<?php

namespace App\Console\Commands;

use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Models\SecretVaultKeyModel;
use App\Services\SecretFileCryptoService;
use Illuminate\Console\Command;

/**
 * 既存ファイルのファイル鍵を、旧方式（SECRETS_MASTER_KEYでのラップ）から
 * 新方式（vault公開鍵でのECDH-ESラップ）へ載せ替える一度きりの移行コマンド。
 *
 * **本文の暗号文は一切作り変えない。** 変えるのは「誰がファイル鍵をアンラップできるか」だけで、
 * /var/encrypted 配下のファイルには触れないため、途中で失敗しても本文が壊れることはない。
 *
 * 実行順序:
 *   1. ブラウザで office.souwake.com の /secrets を開き、vault鍵を登録する（認証器 + リカバリコード）
 *   2. このコマンドを --dry-run で実行して対象件数を確認する
 *   3. 本実行する
 *   4. `php artisan migrate` で 2026_08_08_000003 を適用し、旧カラムを削除する
 *   5. .env から SECRETS_MASTER_KEY を削除する
 */
class SecretsMigrateToVaultCommand extends Command
{
    protected $signature = 'secrets:migrate-to-vault {--dry-run : 対象件数を表示するだけで、実際の書き換えは行わない}';

    protected $description = '既存ファイルのファイル鍵を、マスターキーラップからvault公開鍵ラップへ移行する';

    public function handle(): int
    {
        $vaultPublicKey = SecretVaultKeyModel::publicKeyRaw();
        if ($vaultPublicKey === null) {
            $this->error('vault鍵が未登録です。先にブラウザ（office.souwake.com の /secrets）で認証器を登録してください。');

            return self::FAILURE;
        }

        // 旧カラムが既に削除されている＝移行済みの環境では何もしない
        if (! $this->hasLegacyColumns()) {
            $this->info('旧方式のカラム（wrapped_key）は既に削除されています。移行済みです。');

            return self::SUCCESS;
        }

        $targets = SecretFileModel::whereNotNull('wrapped_key')
            ->whereNull('vault_wrapped_key')
            ->get();

        $this->info("移行対象: {$targets->count()}件");

        if ($this->option('dry-run')) {
            foreach ($targets as $file) {
                $this->line("  id={$file->id} uuid={$file->uuid} name={$file->original_name}");
            }
            $this->comment('--dry-run のため実際の書き換えは行いませんでした。');

            return self::SUCCESS;
        }

        if ($targets->isEmpty()) {
            return self::SUCCESS;
        }

        $migrated = 0;
        $failed = 0;

        foreach ($targets as $file) {
            try {
                // 旧マスターキーでアンラップ（この一瞬だけサーバーが平文のファイル鍵を持つ）
                $fileKey = SecretFileCryptoService::unwrapFileKey(
                    base64_decode($file->wrapped_key),
                    base64_decode($file->key_wrap_nonce),
                    base64_decode($file->key_wrap_tag),
                );

                // vault公開鍵で再ラップ（以降サーバーはアンラップできなくなる）
                $wrapped = SecretFileCryptoService::wrapFileKeyForVault($vaultPublicKey, $fileKey);

                $file->eph_public_key = base64_encode($wrapped['eph_public_key']);
                $file->vault_wrapped_key = base64_encode($wrapped['wrapped_key']);
                $file->vault_wrap_nonce = base64_encode($wrapped['nonce']);
                $file->vault_wrap_tag = base64_encode($wrapped['tag']);
                $file->save();

                // 平文のファイル鍵をメモリ上から早めに潰す（best-effort。ext-sodiumが
                // 無い環境もあるため存在チェックしてから呼ぶ）
                if (function_exists('sodium_memzero')) {
                    sodium_memzero($fileKey);
                }

                $migrated++;
                $this->line("  ✓ id={$file->id} {$file->original_name}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  ✗ id={$file->id} {$file->original_name}: {$e->getMessage()}");
                Utils::log('error', "vault移行に失敗 secrets:migrate-to-vault id={$file->id}\n".$e->getMessage());
            }
        }

        Utils::log('info', "vault移行完了 migrated={$migrated} failed={$failed}");
        $this->info("移行完了: 成功{$migrated}件 / 失敗{$failed}件");

        if ($failed > 0) {
            $this->warn('失敗した行が残っています。旧カラムを削除するマイグレーション（2026_08_08_000003）は適用しないでください。');

            return self::FAILURE;
        }

        $this->comment('次の手順: `php artisan migrate` で旧カラムを削除し、.env から SECRETS_MASTER_KEY を削除してください。');

        return self::SUCCESS;
    }

    private function hasLegacyColumns(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('secret_files', 'wrapped_key');
    }
}
