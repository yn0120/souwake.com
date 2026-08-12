<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('wedding_rsvps', 'country')) {
            return;
        }

        // アメリカから参加されるご家族がいるため、住所の国を保持する。
        // 州はprefectureカラム（日本は都道府県）に入れ、住所の項目数は日米で共通にしている。
        DB::statement(<<<'SQL'
            ALTER TABLE wedding_rsvps
                ADD COLUMN `country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'JP' COMMENT 'ご住所の国 JP=日本 US=アメリカ' AFTER `kana_mei`
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('wedding_rsvps', 'country')) {
            return;
        }

        DB::statement('ALTER TABLE wedding_rsvps DROP COLUMN `country`');
    }
};
