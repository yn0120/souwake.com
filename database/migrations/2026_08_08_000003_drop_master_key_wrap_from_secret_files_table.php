<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * マスターキーによるファイル鍵ラップの列を削除する（E2E化の総仕上げ）。
 *
 * この列が残っている限り、.env の SECRETS_MASTER_KEY とディスクを両方取られた時点で
 * サーバー側だけで復号できてしまい、E2Eの意味がなくなる。
 *
 * **実行前に必ず `php artisan secrets:migrate-to-vault` を完了させること。**
 * このマイグレーションは未移行の行（vault_wrapped_key が NULL の ready 行）が残っていると
 * 中断する。取り返しがつかない削除のため、意図的に安全弁を入れている。
 */
return new class extends Migration
{
    public function up(): void
    {
        $unmigrated = DB::table('secret_files')
            ->where('status', 'ready')
            ->whereNull('vault_wrapped_key')
            ->count();

        if ($unmigrated > 0) {
            throw new RuntimeException(
                "vaultへ未移行のファイルが{$unmigrated}件あります。"
                .'先に `php artisan secrets:migrate-to-vault` を実行してください。'
                .'このまま削除すると該当ファイルは復元不能になります。'
            );
        }

        DB::statement(<<<'SQL'
            ALTER TABLE secret_files
                DROP COLUMN `wrapped_key`,
                DROP COLUMN `key_wrap_nonce`,
                DROP COLUMN `key_wrap_tag`
        SQL);
    }

    public function down(): void
    {
        // 列は戻せるが、マスターキーでラップされた値そのものは復元できない
        // （移行時に平文のファイル鍵を保持していないため）。ロールバックしても旧方式では読めない。
        DB::statement(<<<'SQL'
            ALTER TABLE secret_files
                ADD COLUMN `wrapped_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'マスターキーでラップ（AES-256-GCM）したファイル鍵（base64）' AFTER `size_bytes`,
                ADD COLUMN `key_wrap_nonce` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ファイル鍵ラップ時のGCMノンス（base64、12byte）' AFTER `wrapped_key`,
                ADD COLUMN `key_wrap_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ファイル鍵ラップ時のGCM認証タグ（base64、16byte）' AFTER `key_wrap_nonce`
        SQL);
    }
};
