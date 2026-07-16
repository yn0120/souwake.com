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
            ALTER TABLE admins
                ADD COLUMN `budget_spreadsheet_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '家計簿の保存先スプレッドシートURL' AFTER `last_activity_at`
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE admins DROP COLUMN `budget_spreadsheet_url`');
    }
};
