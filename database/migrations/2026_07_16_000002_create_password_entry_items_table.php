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
            CREATE TABLE IF NOT EXISTS password_entry_items (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '項目ID',
                `password_entry_id` bigint unsigned NOT NULL COMMENT 'サイトID password_entries.id',
                `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '項目名（ログインID、パスワード、秘密の質問 等 自由入力）',
                `type` enum('text','email','password','tel','textarea') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '入力タイプ',
                `value` text COLLATE utf8mb4_unicode_ci COMMENT '値（type=passwordの場合のみ暗号化して保存）',
                `display_order` int NOT NULL DEFAULT 0 COMMENT 'サイト内での表示順',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '論理削除日時',
                PRIMARY KEY (`id`),
                KEY `idx_use_password_entry_id` (`password_entry_id`),
                KEY `idx_use_deleted_at` (`deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='パスワード管理（サイト配下の項目）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS password_entry_items');
    }
};
