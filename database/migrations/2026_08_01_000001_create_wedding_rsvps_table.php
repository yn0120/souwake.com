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
            CREATE TABLE IF NOT EXISTS wedding_rsvps (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '回答ID',
                `attendance` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '出欠 attending=出席 absent=欠席',
                `name_sei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'お名前（姓・漢字）',
                `name_mei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'お名前（名・漢字）',
                `kana_sei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'フリガナ（姓）',
                `kana_mei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'フリガナ（名）',
                `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '郵便番号',
                `prefecture` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '都道府県',
                `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '市区町村',
                `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '番地',
                `building` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '建物名',
                `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '電話番号',
                `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'メールアドレス',
                `allergy` text COLLATE utf8mb4_unicode_ci COMMENT 'アレルギー・お食事のご要望',
                `arrival_date` date DEFAULT NULL COMMENT '沖縄への到着日',
                `departure_date` date DEFAULT NULL COMMENT '沖縄からの出発日',
                `hotel_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '宿泊先ホテル名',
                `costume_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '当日衣装（かりゆしウェア等）のサイズ',
                `companion_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '同伴者の有無 1=あり 0=なし',
                `companion_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '同伴者お名前',
                `companion_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '同伴者フリガナ',
                `companion_meal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '同伴者のお食事 adult=大人メニュー child_lunch=お子様ランチ child_plate=お子様プレート none=不要',
                `child_info` text COLLATE utf8mb4_unicode_ci COMMENT 'お子様連れの場合の追加情報（年齢・ベビーカーの有無など）',
                `message` text COLLATE utf8mb4_unicode_ci COMMENT '新郎新婦へのメッセージ',
                `song_request` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '楽曲リクエスト',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`id`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='結婚式 出欠回答（RSVP）'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS wedding_rsvps');
    }
};
