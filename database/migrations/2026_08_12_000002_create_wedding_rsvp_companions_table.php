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
            CREATE TABLE IF NOT EXISTS wedding_rsvp_companions (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '同伴者ID',
                `wedding_rsvp_id` bigint unsigned NOT NULL COMMENT '紐づく回答ID wedding_rsvps.id',
                `sort_no` int unsigned NOT NULL DEFAULT 0 COMMENT 'フォーム上の並び順（0始まり）',
                `name_sei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '同伴者お名前（姓・漢字）',
                `name_mei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '同伴者お名前（名・漢字）',
                `kana_sei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '同伴者フリガナ（姓）',
                `kana_mei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '同伴者フリガナ（名）',
                `meal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '同伴者のお食事 adult=大人メニュー child_lunch=お子様ランチ child_plate=お子様プレート none=不要',
                `child_info` text COLLATE utf8mb4_unicode_ci COMMENT 'お子様連れの場合の追加情報（年齢・ベビーカーの有無など）',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`id`),
                KEY `idx_wedding_rsvp_id` (`wedding_rsvp_id`, `sort_no`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='結婚式 出欠回答の同伴者（連名。1回答に複数）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS wedding_rsvp_companions');
    }
};
