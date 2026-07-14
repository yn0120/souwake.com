<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS secret_files (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '秘密ファイルID',
                `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '/var/encrypted 配下の実ファイル名（暗号文の識別子。AADにも使用）',
                `admin_id` bigint unsigned NOT NULL COMMENT 'アップロードした管理者ID admins.id',
                `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '元のファイル名（表示用）',
                `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '圧縮後・暗号化前のMIMEタイプ',
                `size_bytes` bigint unsigned DEFAULT NULL COMMENT '圧縮後・暗号化前（平文）のサイズ。復号ストリーミング配信のContent-Length算出に使用',
                `wrapped_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'マスターキーでラップ（AES-256-GCM）したファイル鍵（base64）',
                `key_wrap_nonce` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ファイル鍵ラップ時のGCMノンス（base64、12byte）',
                `key_wrap_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ファイル鍵ラップ時のGCM認証タグ（base64、16byte）',
                `content_nonce_base` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '本文チャンク暗号化の基準ノンス（base64、12byte。chunk_indexと組み合わせてチャンクごとのノンスを導出）',
                `status` enum('uploading','processing','ready','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploading' COMMENT 'uploading=チャンク受信中 processing=圧縮/暗号化中 ready=閲覧可能 failed=失敗',
                `staging_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '処理中の平文一時ファイルパス（7日抹消時に道連れで削除するため記録）',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_uniq_uuid` (`uuid`),
                KEY `idx_use_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='秘密ファイル管理（暗号化保管・7日無操作で完全抹消）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS secret_files');
    }
};
