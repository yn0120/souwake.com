<?php

namespace App\Console\Commands;

use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Services\SecureDeleteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ファイル機能のデッドマンズスイッチ本体。
 * 管理者(admins.id=config('secrets.admin_id')、既定1)の最終アクティビティが
 * config('secrets.retention_days')（既定7日）を1秒でも超えたら、ファイルをすべて
 * 復元不能な形で抹消する。routes/console.phpのSchedule::commandから定期実行される。
 *
 * 抹消の一番の拠り所は「鍵の破棄（crypto-shred）」で、DBのファイル行を削除した時点で
 * 暗号文は計算量的に復元不能になる。ディスク上の暗号文の上書き削除はあくまで防御的な追加策。
 */
class SecretsEnforceRetentionCommand extends Command
{
    protected $signature = 'secrets:enforce-retention {--force : 最終アクティビティの経過日数を無視して即座に全ファイルを抹消する}';

    protected $description = '管理者の最終アクティビティから規定日数を超えた場合、ファイルをすべて復元不能な形で抹消する';

    /** フリーズ状態でHorizonの残ジョブが捌けるのを待つ最大時間（秒） */
    private const DRAIN_TIMEOUT_SECONDS = 300;

    private const DRAIN_POLL_INTERVAL_SECONDS = 10;

    public function handle(): int
    {
        $adminId = (int) config('secrets.admin_id');
        $retentionDays = (int) config('secrets.retention_days', 7);

        $force = (bool) $this->option('force');

        if (! $force) {
            $lastActivityAt = DB::table('admins')->where('id', $adminId)->value('last_activity_at');

            if (! $lastActivityAt) {
                // 一度もアクティビティが記録されていない場合は判定不能なため何もしない
                return self::SUCCESS;
            }

            // Carbon 3ではdiffInSecondsのデフォルトが符号付きに変わったため、絶対値で経過秒数を取る
            $inactiveSeconds = now()->diffInSeconds(\Illuminate\Support\Carbon::parse($lastActivityAt), absolute: true);
            if ($inactiveSeconds <= $retentionDays * 86400) {
                return self::SUCCESS;
            }

            $this->warn("最終アクティビティから{$retentionDays}日を超過（{$inactiveSeconds}秒経過）。ファイルの抹消を開始します。");
            Utils::log('info', "ファイル抹消トリガー secrets:enforce-retention inactive_seconds={$inactiveSeconds}");
        } else {
            $this->warn('--forceが指定されたため、最終アクティビティの経過日数を無視して即座に全ファイルを抹消します。');
            Utils::log('info', 'ファイル抹消トリガー secrets:enforce-retention --force（手動実行）');
        }

        // 新規アップロードの開始・継続を即座に拒否する（以降の新規流入を止めてから抹消する）
        Cache::put('secrets:frozen', true, now()->addHours(2));

        try {
            $this->drainInFlightUploads();
            $wipedCount = $this->wipeAll();
            Utils::log('info', "ファイル抹消完了 wiped={$wipedCount}");
            $this->info("抹消完了: {$wipedCount}件");
        } finally {
            Cache::forget('secrets:frozen');
        }

        return self::SUCCESS;
    }

    /**
     * uploading/processing状態のジョブが捌けるのを一定時間待つ（タイムアウトしても続行する）。
     * 閲覧中のストリームは待たない（放置された閲覧タブが抹消を妨害できてしまうため）。
     */
    private function drainInFlightUploads(): void
    {
        $waited = 0;
        while ($waited < self::DRAIN_TIMEOUT_SECONDS) {
            $inFlight = SecretFileModel::getBy(['status' => ['uploading', 'processing'], 'method' => 'count']);
            if ($inFlight === 0) {
                return;
            }
            sleep(self::DRAIN_POLL_INTERVAL_SECONDS);
            $waited += self::DRAIN_POLL_INTERVAL_SECONDS;
        }

        Utils::log('info', 'secrets:enforce-retention 処理中ジョブのドレイン待ちがタイムアウトしたため強制続行します。');
    }

    /**
     * 全ファイルを抹消する。DB行の削除（鍵の破棄）を主、ディスク上の暗号文の上書き削除を副とする。
     * ステージング（平文一時ファイル）も道連れで削除する。
     */
    private function wipeAll(): int
    {
        $files = SecretFileModel::all();
        $count = $files->count();

        foreach ($files as $file) {
            $ciphertextPath = Storage::disk('secrets')->path($file->uuid);
            SecureDeleteService::wipeFile($ciphertextPath);

            if ($file->staging_path) {
                SecureDeleteService::wipeFile($file->staging_path);
            }
        }

        // DB行の削除（crypto-shred: ラップ済みファイル鍵が失われた時点で暗号文は復元不能になる）
        DB::table('secret_files')->truncate();

        // 万一DB行と紐付かない孤立した一時ファイルが残っていた場合の掃除
        SecureDeleteService::wipeDirectoryContents(Storage::disk('secrets_tmp')->path(''));

        return $count;
    }
}
