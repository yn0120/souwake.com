<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ファイル鍵のラップ先を「サーバーのSECRETS_MASTER_KEY」から「vaultの公開鍵」へ移すためのカラム追加。
 *
 * これまではサーバーが .env のマスターキーでファイル鍵をアンラップし、PHP側で復号して平文を返していた。
 * その方式ではCloudflareのエッジにも、.env とディスクを取られた攻撃者にも平文が渡ってしまうため、
 * P-256 ECDH-ES（一時鍵とvault公開鍵のECDH + HKDF-SHA256 + AES-256-GCM）でラップし直し、
 * アンラップできるのはvault秘密鍵を復号できるブラウザだけ、という状態にする。
 *
 * 既存の wrapped_key / key_wrap_nonce / key_wrap_tag は移行期間中そのまま残す
 * （secrets:migrate-to-vault が旧マスターキーでアンラップ→新方式で再ラップするために必要）。
 * 移行完了後に 2026_08_08_000003 で削除する。本文の暗号文は一切再暗号化しない。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE secret_files
                ADD COLUMN `eph_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ファイルごとに生成した一時P-256公開鍵（SPKI DERのbase64）。vault秘密鍵とのECDHで鍵導出に使う' AFTER `size_bytes`,
                ADD COLUMN `vault_wrapped_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ECDH-ES由来の鍵でAES-256-GCMラップしたファイル鍵（base64）' AFTER `eph_public_key`,
                ADD COLUMN `vault_wrap_nonce` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'vaultラップ時のGCMノンス（base64、12byte）' AFTER `vault_wrapped_key`,
                ADD COLUMN `vault_wrap_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'vaultラップ時のGCM認証タグ（base64、16byte）' AFTER `vault_wrap_nonce`
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE secret_files
                DROP COLUMN `eph_public_key`,
                DROP COLUMN `vault_wrapped_key`,
                DROP COLUMN `vault_wrap_nonce`,
                DROP COLUMN `vault_wrap_tag`
        SQL);
    }
};
