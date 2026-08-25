<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CACHE_STORE=redis のため database キャッシュドライバ用テーブルは未使用。
        // cache_locks は redis ドライバでは RedisLock（Redis自体のNXロック）が使われるため不要。
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');

        // QUEUE_CONNECTION=redis のため database キュードライバ用テーブルは未使用。
        // failed_jobs は QUEUE_CONNECTION と独立した設定（QUEUE_FAILED_DRIVER）のため残す。
        Schema::dropIfExists('jobs');

        // Bus::batch() を使用していないため job_batches は不要。
        // 将来バッチ処理を導入する場合は down() の定義を参考に復元すること。
        Schema::dropIfExists('job_batches');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }
};
