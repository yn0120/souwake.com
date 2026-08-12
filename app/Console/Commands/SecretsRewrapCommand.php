<?php

namespace App\Console\Commands;

use App\Libraries\Utils;
use App\Models\SecretFileModel;
use App\Services\SecretFileCryptoService;
use Illuminate\Console\Command;

/**
 * SECRETS_MASTER_KEYのローテーション用コマンド。
 * .envを漏洩の疑いがある等の理由で新しいマスターキーに切り替えた後、
 * 旧マスターキーで各ファイルのラップ済みファイル鍵をアンラップし、現在のマスターキーで再ラップする。
 *
 * 本文（暗号化ファイル本体）はファイル鍵自体を変更しないため再暗号化不要（DBの鍵情報だけ差し替わる）。
 */
class SecretsRewrapCommand extends Command
{
    protected $signature = 'secrets:rewrap {--old-key= : ローテーション前のSECRETS_MASTER_KEY（base64、base64:プレフィックス可）}';

    protected $description = 'SECRETS_MASTER_KEYのローテーションに伴い、各ファイルのラップ済み鍵を新しいマスターキーで再ラップする';

    public function handle(): int
    {
        $oldKeyOption = $this->option('old-key');
        if (! $oldKeyOption) {
            $this->error('--old-key に旧SECRETS_MASTER_KEYを指定してください。');

            return self::FAILURE;
        }

        try {
            $oldMasterKey = SecretFileCryptoService::decodeKeyString($oldKeyOption);
        } catch (\Throwable $e) {
            $this->error('--old-key の形式が不正です: '.$e->getMessage());

            return self::FAILURE;
        }

        $targets = SecretFileModel::getBy(['status' => 'ready']);
        $this->info("対象ファイル数: {$targets->count()}");

        $success = 0;
        $failed = 0;

        foreach ($targets as $file) {
            try {
                $fileKey = SecretFileCryptoService::unwrapFileKeyUsing(
                    $oldMasterKey,
                    base64_decode($file->wrapped_key),
                    base64_decode($file->key_wrap_nonce),
                    base64_decode($file->key_wrap_tag),
                );

                $rewrapped = SecretFileCryptoService::wrapFileKey($fileKey);

                $file->wrapped_key = base64_encode($rewrapped['wrapped_key']);
                $file->key_wrap_nonce = base64_encode($rewrapped['nonce']);
                $file->key_wrap_tag = base64_encode($rewrapped['tag']);
                $file->save();

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                Utils::log('error', "ファイル鍵の再ラップに失敗 secrets:rewrap#{$file->id}\n".$e->getMessage());
            }
        }

        $this->info("再ラップ完了: 成功{$success}件 / 失敗{$failed}件");

        if ($failed > 0) {
            $this->warn('失敗した件数がある場合、--old-keyの指定が正しいか確認してください。');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
