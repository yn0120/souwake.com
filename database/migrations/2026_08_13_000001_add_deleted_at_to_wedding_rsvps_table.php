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
        if (Schema::hasColumn('wedding_rsvps', 'deleted_at')) {
            return;
        }

        // 管理画面（admin.souwake.com）から回答を削除できるようにする。
        // 他の管理対象テーブルと同じく物理削除はせず、deleted_atによる論理削除とする。
        DB::statement(<<<'SQL'
            ALTER TABLE wedding_rsvps
                ADD COLUMN `deleted_at` datetime DEFAULT NULL COMMENT '論理削除日時',
                ADD KEY `idx_use_deleted_at` (`deleted_at`)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('wedding_rsvps', 'deleted_at')) {
            return;
        }

        DB::statement('ALTER TABLE wedding_rsvps DROP KEY `idx_use_deleted_at`, DROP COLUMN `deleted_at`');
    }
};
