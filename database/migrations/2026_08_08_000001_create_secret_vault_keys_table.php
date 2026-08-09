<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ファイル機能のE2E暗号化で使う「vault鍵ペア」を保管するテーブル。
 *
 * P-256のECDH鍵ペアを1組だけ持ち、
 *   - 公開鍵 (public_key) はサーバーが平文で持つ。アップロード時のファイル鍵ラップに使う。
 *     サーバーは「ラップはできるがアンラップはできない」状態になる。
 *   - 秘密鍵 (wrapped_private_key) はサーバーには暗号文としてしか存在しない。
 *     ブラウザがWebAuthn PRF等から導出した鍵でAES-256-GCM復号して初めて使える。
 *
 * 同一の秘密鍵を複数の手段でラップした行を並べて持つ（全行のpublic_keyは同じ値になる）。
 * これは認証器を1つ紛失しただけで全データが失われるのを避けるための設計で、
 *   - kind='webauthn': 認証器ごとに1行（メイン + バックアップYubiKey等）
 *   - kind='recovery': 32byteのランダムなリカバリコードでラップした行（紙に控える用）
 * を想定している。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS secret_vault_keys (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'vault鍵ID',
                `kind` enum('webauthn','recovery') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'webauthn=認証器のPRF出力でラップ recovery=リカバリコードでラップ',
                `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '識別用の表示名（例: MacBook Touch ID / 予備YubiKey / リカバリコード）',
                `credential_id` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WebAuthnのcredential ID（base64url）。kind=webauthnのみ。navigator.credentials.get()のallowCredentialsに渡す',
                `prf_salt` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WebAuthn PRF拡張のeval.firstに渡すsalt（base64、32byte）。kind=webauthnのみ',
                `recovery_salt` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'リカバリコードのHKDFに使うsalt（base64、32byte）。kind=recoveryのみ',
                `public_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-256 ECDH公開鍵（SPKI DERのbase64）。全行同じ値が入る。ファイル鍵のラップに使う',
                `wrapped_private_key` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-256 ECDH秘密鍵（PKCS8 DER）をvault鍵でAES-256-GCM暗号化したもの（base64）',
                `wrap_nonce` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '秘密鍵ラップ時のGCMノンス（base64、12byte）',
                `wrap_tag` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '秘密鍵ラップ時のGCM認証タグ（base64、16byte）',
                `last_used_at` datetime DEFAULT NULL COMMENT '最終アンロック日時（どの手段が生きているかの確認用）',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_uniq_credential_id` (`credential_id`),
                KEY `idx_use_kind` (`kind`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ファイル機能のE2E暗号化用vault鍵（秘密鍵はブラウザでしか開けない）'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS secret_vault_keys');
    }
};
