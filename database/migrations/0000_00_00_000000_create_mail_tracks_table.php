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
            CREATE TABLE IF NOT EXISTS mail_tracks (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'トラッキングID',
                `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SQSメッセージID',
                `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '送信先メールアドレス',
                `token` longtext COLLATE utf8mb4_unicode_ci COMMENT 'トラッキングトークン',
                `sent_at` datetime DEFAULT NULL COMMENT '送信日時',
                `opened_at` datetime DEFAULT NULL COMMENT '開封日時',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                `deleted_at` datetime DEFAULT NULL COMMENT '削除日時',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メールトラッキング（開封/未開封）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS mail_tracks');
    }
};
