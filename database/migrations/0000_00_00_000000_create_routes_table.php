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
            CREATE TABLE IF NOT EXISTS routes (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ルートID',
                `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '機能名 管理者一覧,管理者登録',
                `sys_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RouteName officeAdminIndex,officeAdminCreate*',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '削除日時',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ルートマスター'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS routes');
    }
};
