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
        if (! Schema::hasColumn('wedding_rsvps', 'costume_size')) {
            return;
        }

        // サイズ選択（かりゆしウェアの貸与）から、受取場所（レストラン／会場）の選択へ仕様変更
        DB::statement(<<<'SQL'
            ALTER TABLE wedding_rsvps
                CHANGE `costume_size` `kariyushi_pickup_location` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'かりゆしウェア受取場所 restaurant=Hillside Grill Restaurant venue=会場'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('wedding_rsvps', 'kariyushi_pickup_location')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE wedding_rsvps
                CHANGE `kariyushi_pickup_location` `costume_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '当日衣装（かりゆしウェア等）のサイズ'
        SQL);
    }
};
