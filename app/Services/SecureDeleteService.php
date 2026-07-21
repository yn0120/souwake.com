<?php

namespace App\Services;

/**
 * ファイルの安全な削除を担うサービス。
 *
 * 抹消の一番の拠り所は「鍵の破棄（crypto-shred）」であり、本サービスの上書き削除は
 * あくまで防御的な追加策（best-effort）に過ぎない。SSDのウェアレベリングやCoWファイルシステム、
 * バックアップ/スナップショットが存在する場合は上書きだけでは消えないため、過信しないこと。
 * そのため多重パスは行わず、1パス+fsyncのみに留めている（詳細はREADME/実装コメント参照）。
 */
class SecureDeleteService
{
    private const OVERWRITE_CHUNK_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * 指定したファイルをランダムデータで1パス上書きし、fsyncしてから
     * ランダムな名前にリネームして削除する。
     */
    public static function wipeFile(string $absolutePath): void
    {
        if (! is_file($absolutePath)) {
            return;
        }

        $size = filesize($absolutePath);
        $handle = @fopen($absolutePath, 'r+b');

        if ($handle === false) {
            // 上書きできない場合でも、削除だけは試みる
            @unlink($absolutePath);

            return;
        }

        $remaining = $size === false ? 0 : $size;
        while ($remaining > 0) {
            $writeSize = min(self::OVERWRITE_CHUNK_SIZE, $remaining);
            fwrite($handle, random_bytes($writeSize));
            $remaining -= $writeSize;
        }

        fflush($handle);
        if (function_exists('fsync')) {
            fsync($handle);
        }
        fclose($handle);

        $randomName = dirname($absolutePath).'/'.bin2hex(random_bytes(16)).'.deleted';
        if (@rename($absolutePath, $randomName)) {
            @unlink($randomName);
        } else {
            @unlink($absolutePath);
        }
    }

    /**
     * ディレクトリ配下の全ファイルをwipeFile()で削除する（ディレクトリ自体は残す）。
     * .gitkeepはgit管理用のプレースホルダーのため対象外とする。
     */
    public static function wipeDirectoryContents(string $absoluteDirectoryPath): void
    {
        if (! is_dir($absoluteDirectoryPath)) {
            return;
        }

        $items = @scandir($absoluteDirectoryPath) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }

            $path = $absoluteDirectoryPath.'/'.$item;
            if (is_file($path)) {
                self::wipeFile($path);
            }
        }
    }
}
