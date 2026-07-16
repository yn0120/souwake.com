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
            CREATE TABLE IF NOT EXISTS password_entries (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'サイトID',
                `admin_id` bigint unsigned NOT NULL COMMENT '所有者の管理者ID admins.id',
                `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'サイト名',
                `display_order` int NOT NULL DEFAULT 0 COMMENT '表示順（手動で固定するデフォルト表示順）',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '論理削除日時',
                PRIMARY KEY (`id`),
                KEY `idx_use_admin_id` (`admin_id`),
                KEY `idx_use_deleted_at` (`deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='パスワード管理（サイト単位）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS password_entries');
    }
};
