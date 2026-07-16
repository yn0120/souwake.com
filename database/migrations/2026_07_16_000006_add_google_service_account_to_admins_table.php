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
                ADD COLUMN `google_service_account_json_base64` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '家計簿機能で使うGoogleサービスアカウントJSON鍵（base64エンコード済み・管理者ごと）' AFTER `budget_spreadsheet_url`,
                ADD COLUMN `google_service_account_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'アップロードされたサービスアカウントのメールアドレス（共有先確認の表示用）' AFTER `google_service_account_json_base64`
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE admins
                DROP COLUMN `google_service_account_json_base64`,
                DROP COLUMN `google_service_account_email`
        SQL);
    }
};
