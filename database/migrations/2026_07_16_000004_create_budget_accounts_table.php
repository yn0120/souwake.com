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
            CREATE TABLE IF NOT EXISTS budget_accounts (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '口座ID',
                `admin_id` bigint unsigned NOT NULL COMMENT '所有者の管理者ID admins.id',
                `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '口座名',
                `display_order` int NOT NULL DEFAULT 0 COMMENT '表示順',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '論理削除日時',
                PRIMARY KEY (`id`),
                KEY `idx_use_admin_id` (`admin_id`),
                KEY `idx_use_deleted_at` (`deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='家計簿の口座マスター（管理者ごと）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS budget_accounts');
    }
};
