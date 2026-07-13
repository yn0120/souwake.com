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
            CREATE TABLE IF NOT EXISTS admins (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '管理者ID',
                `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '氏名',
                `role_id` bigint unsigned DEFAULT NULL COMMENT '権限ID roles.id',
                `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メールアドレス',
                `password` text COLLATE utf8mb4_unicode_ci COMMENT 'ハッシュ化パスワード',
                `onetime_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ログイン用ワンタイムキー',
                `onetime_key_created_at` datetime DEFAULT NULL COMMENT 'ログイン用ワンタイムキー発行日時',
                `remember_token` text COLLATE utf8mb4_unicode_ci COMMENT 'パスワード設定トークン',
                `login_locked_at` datetime DEFAULT NULL COMMENT 'ログインロック日時',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `activated_at` datetime DEFAULT NULL COMMENT '有効化日時（初期パスワード設定日時）',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `terminated_at` datetime DEFAULT NULL COMMENT '退職日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '削除日時',
                PRIMARY KEY (`id`),
                KEY `idx_use_role_id` (`role_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理者マスター'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS admins');
    }
};
