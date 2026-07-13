<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS role_route (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `role_id` bigint unsigned DEFAULT NULL COMMENT '権限ID roles.id',
                `route_id` bigint unsigned DEFAULT NULL COMMENT 'ルートID routes.id',
                `is_allowed` tinyint unsigned DEFAULT '1' COMMENT '操作フラグ 0:不可能, 1:可能',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '削除日時',
                PRIMARY KEY (`id`),
                KEY `idx_rrr_route_id` (`route_id`),
                KEY `idx_rrr_role_id` (`role_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='権限とルートのリレーション'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS role_route');
    }
};
