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
            CREATE TABLE IF NOT EXISTS wedding_rsvp_photos (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '画像ID',
                `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '画像の識別子（storage上のファイル名・配信URLに使用）',
                `wedding_rsvp_id` bigint unsigned DEFAULT NULL COMMENT '紐づく回答ID wedding_rsvps.id（フォーム送信前はNULL）',
                `session_token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'アップロードしたブラウザの識別トークン（localStorage保持。削除・復元時の本人確認に使用）',
                `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '元のファイル名（表示用）',
                `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '変換後のMIMEタイプ',
                `size_bytes` bigint unsigned DEFAULT NULL COMMENT '変換後のファイルサイズ',
                `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'wedding_photosディスク上の相対パス（変換後）',
                `staging_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '変換前の一時ファイルの相対パス（処理完了時にNULL）',
                `status` enum('pending','processing','ready','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending=キュー投入済 processing=変換中 ready=表示可能 failed=失敗',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_uniq_uuid` (`uuid`),
                KEY `idx_wedding_rsvp_id` (`wedding_rsvp_id`),
                KEY `idx_session_token` (`session_token`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='結婚式 出欠回答に添付されたお祝い画像'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS wedding_rsvp_photos');
    }
};
