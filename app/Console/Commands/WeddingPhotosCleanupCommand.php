<?php

namespace App\Console\Commands;

use App\Http\Controllers\Wedding\WeddingRsvpPhotoController;
use App\Libraries\Utils;
use App\Models\WeddingRsvpPhotoModel;
use Illuminate\Console\Command;

/**
 * 結婚式サイトのお祝い画像のうち、フォーム送信までたどり着かなかった（回答に紐づいていない）
 * 画像を一定日数後に削除する。ゲストがアップロードだけして離脱したケースが積み上がると
 * ストレージを圧迫するため、routes/console.phpから1日1回実行する。
 *
 * 保持日数の間はブラウザのlocalStorageからの復元が効くため、短くしすぎないこと。
 */
class WeddingPhotosCleanupCommand extends Command
{
    protected $signature = 'wedding:photos-cleanup {--days=14 : 未送信のまま保持する日数}';

    protected $description = '結婚式サイトの未送信（回答未紐づけ）のお祝い画像を削除する';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $photos = WeddingRsvpPhotoModel::whereNull('wedding_rsvp_id')
            ->where('created_at', '<', $threshold)
            ->get();

        if ($photos->isEmpty()) {
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($photos as $photo) {
            try {
                WeddingRsvpPhotoController::deleteFiles($photo);
                $photo->delete();
                $deleted++;
            } catch (\Throwable $e) {
                Utils::log('error', "未送信のお祝い画像の削除に失敗 uuid={$photo->uuid}\n{$e}");
            }
        }

        $this->info("未送信のお祝い画像を{$deleted}件削除しました。");
        Utils::log('info', "未送信のお祝い画像を削除 wedding:photos-cleanup deleted={$deleted} days={$days}");

        return self::SUCCESS;
    }
}
