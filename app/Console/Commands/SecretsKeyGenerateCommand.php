<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 秘密ファイル機能のマスターキー（SECRETS_MASTER_KEY）を生成し .env に書き込む。
 * APP_KEYとは別に管理する独立したキーのため、標準の `key:generate` とは別コマンドにしている。
 */
class SecretsKeyGenerateCommand extends Command
{
    protected $signature = 'secrets:key-generate {--force : 既存のSECRETS_MASTER_KEYを上書きする}';

    protected $description = '秘密ファイル機能のマスターキー(SECRETS_MASTER_KEY)を生成する';

    public function handle(): int
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            $this->error('.envファイルが見つかりません。');

            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);
        $hasExisting = (bool) preg_match('/^SECRETS_MASTER_KEY=(.*)$/m', $envContent, $matches);

        if ($hasExisting && trim($matches[1] ?? '') !== '' && ! $this->option('force')) {
            $this->error('SECRETS_MASTER_KEYは既に設定されています。上書きする場合は --force を指定してください。');
            $this->warn('既存のキーを上書きすると、それまでに暗号化された秘密ファイルは復号できなくなります。事前に secrets:rewrap の実行を検討してください。');

            return self::FAILURE;
        }

        $newKey = 'base64:'.base64_encode(random_bytes(32));

        if ($hasExisting) {
            $envContent = preg_replace('/^SECRETS_MASTER_KEY=.*$/m', "SECRETS_MASTER_KEY={$newKey}", $envContent);
        } else {
            $envContent = rtrim($envContent)."\nSECRETS_MASTER_KEY={$newKey}\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info('SECRETS_MASTER_KEYを生成し.envに書き込みました。');

        return self::SUCCESS;
    }
}
