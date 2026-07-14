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
                ADD COLUMN `last_activity_at` datetime DEFAULT NULL COMMENT '最終アクティビティ日時（秘密ファイル機能の7日抹消判定に使用）' AFTER `terminated_at`
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE admins DROP COLUMN `last_activity_at`');
    }
};
