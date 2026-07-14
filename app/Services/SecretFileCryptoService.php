<?php

namespace App\Services;

use RuntimeException;

/**
 * 秘密ファイル機能の封筒暗号化（envelope encryption）を担うサービス。
 *
 * - ファイルごとにランダムなファイル鍵を生成し、マスターキー（SECRETS_MASTER_KEY）でラップして保管する。
 * - 本文はチャンク単位（chunk_size）のAES-256-GCMで独立に暗号化・認証する（chunked AEAD）。
 *   HTTP Rangeリクエスト（動画のシーク等）でチャンク単位のランダムアクセス復号ができるようにするためで、
 *   AAD（関連データ）にファイルUUID・チャンクindex・最終チャンクフラグを含めることで
 *   チャンクの差し替え・順序入替・末尾切り詰めも検知できる。
 * - 抹消（crypto-shred）は、このサービスを介さずDB側のwrapped_key等の行を削除するだけで、
 *   本文の暗号文がディスク上に残っていても計算量的に復元不能になる。
 */
class SecretFileCryptoService
{
    private const KEY_LEN = 32;   // AES-256
    private const NONCE_LEN = 12; // GCM推奨の96bit
    private const TAG_LEN = 16;

    /**
     * ランダムなファイル鍵を生成する
     */
    public static function generateFileKey(): string
    {
        return random_bytes(self::KEY_LEN);
    }

    /**
     * 本文チャンク暗号化用の基準ノンスを生成する
     */
    public static function generateContentNonceBase(): string
    {
        return random_bytes(self::NONCE_LEN);
    }

    /**
     * 本文の暗号化チャンクサイズ（バイト）
     */
    public static function chunkSize(): int
    {
        return (int) config('secrets.chunk_size', 1024 * 1024);
    }

    /**
     * 暗号化チャンク1件あたりのGCM認証タグのバイト数
     */
    public static function tagLength(): int
    {
        return self::TAG_LEN;
    }

    /**
     * マスターキーでファイル鍵をラップ（AES-256-GCM）する
     *
     * @return array{wrapped_key: string, nonce: string, tag: string} 生バイト列（呼び出し側でbase64化する）
     */
    public static function wrapFileKey(string $fileKey): array
    {
        $nonce = random_bytes(self::NONCE_LEN);
        $tag = '';
        $wrapped = openssl_encrypt(
            $fileKey,
            'aes-256-gcm',
            self::masterKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LEN,
        );

        if ($wrapped === false) {
            throw new RuntimeException('ファイル鍵のラップに失敗しました。');
        }

        return ['wrapped_key' => $wrapped, 'nonce' => $nonce, 'tag' => $tag];
    }

    /**
     * マスターキーでファイル鍵をアンラップする
     */
    public static function unwrapFileKey(string $wrappedKey, string $nonce, string $tag): string
    {
        return self::unwrapFileKeyUsing(self::masterKey(), $wrappedKey, $nonce, $tag);
    }

    /**
     * 指定したマスターキー（生バイト列）でファイル鍵をアンラップする。
     * `secrets:rewrap`（マスターキーローテーション）で、現在の設定とは異なる旧マスターキーを使う場合に使う。
     */
    public static function unwrapFileKeyUsing(string $masterKeyRaw, string $wrappedKey, string $nonce, string $tag): string
    {
        $fileKey = openssl_decrypt(
            $wrappedKey,
            'aes-256-gcm',
            $masterKeyRaw,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        if ($fileKey === false) {
            throw new RuntimeException('ファイル鍵のアンラップに失敗しました（マスターキー不一致または改ざんの可能性）。');
        }

        return $fileKey;
    }

    /**
     * 平文チャンクを暗号化する。戻り値は [暗号文][16byteタグ] を連結したバイト列。
     */
    public static function encryptChunk(string $fileKey, string $nonceBase, int $chunkIndex, bool $isLast, string $uuid, string $plaintext): string
    {
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $fileKey,
            OPENSSL_RAW_DATA,
            self::deriveChunkNonce($nonceBase, $chunkIndex),
            $tag,
            self::buildAad($uuid, $chunkIndex, $isLast),
            self::TAG_LEN,
        );

        if ($ciphertext === false) {
            throw new RuntimeException("チャンク({$chunkIndex})の暗号化に失敗しました。");
        }

        return $ciphertext.$tag;
    }

    /**
     * 暗号化チャンク（[暗号文][16byteタグ]）を復号・認証する。
     * 認証に失敗した場合（改ざん・破損・チャンク差し替え等）は例外を投げ、呼び出し側は即座に配信を打ち切ること。
     */
    public static function decryptChunk(string $fileKey, string $nonceBase, int $chunkIndex, bool $isLast, string $uuid, string $encryptedChunk): string
    {
        if (strlen($encryptedChunk) < self::TAG_LEN) {
            throw new RuntimeException("チャンク({$chunkIndex})のサイズが不正です。");
        }

        $tag = substr($encryptedChunk, -self::TAG_LEN);
        $ciphertext = substr($encryptedChunk, 0, -self::TAG_LEN);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $fileKey,
            OPENSSL_RAW_DATA,
            self::deriveChunkNonce($nonceBase, $chunkIndex),
            $tag,
            self::buildAad($uuid, $chunkIndex, $isLast),
        );

        if ($plaintext === false) {
            throw new RuntimeException("チャンク({$chunkIndex})の復号に失敗しました（改ざんまたは破損の可能性）。");
        }

        return $plaintext;
    }

    /**
     * チャンクindexから、そのチャンク専用のノンスを導出する（基準ノンスの下4byteをカウンタとして加算）
     */
    private static function deriveChunkNonce(string $nonceBase, int $chunkIndex): string
    {
        if (strlen($nonceBase) !== self::NONCE_LEN) {
            throw new RuntimeException('ノンス長が不正です。');
        }

        $counter = unpack('N', substr($nonceBase, -4))[1];
        $counter = ($counter + $chunkIndex) & 0xFFFFFFFF;

        return substr($nonceBase, 0, self::NONCE_LEN - 4).pack('N', $counter);
    }

    /**
     * AAD（関連データ）。ファイルUUID・チャンクindex・最終チャンクフラグを紐付けることで、
     * 別ファイルとのチャンク差し替えや、順序入替・末尾切り詰めを検知できるようにする。
     */
    private static function buildAad(string $uuid, int $chunkIndex, bool $isLast): string
    {
        return $uuid.'|'.$chunkIndex.'|'.($isLast ? '1' : '0');
    }

    private static function masterKey(): string
    {
        $configured = config('secrets.master_key');
        if (! $configured) {
            throw new RuntimeException('SECRETS_MASTER_KEY が設定されていません。');
        }

        return self::decodeKeyString($configured);
    }

    /**
     * `base64:` プレフィックス付き/なしのbase64文字列を、検証付きで生バイト列にデコードする。
     * `secrets:rewrap` がCLIオプションで渡された旧マスターキーをデコードする際にも使う。
     */
    public static function decodeKeyString(string $configured): string
    {
        $encoded = str_starts_with($configured, 'base64:') ? substr($configured, 7) : $configured;
        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== self::KEY_LEN) {
            throw new RuntimeException('マスターキーの形式が不正です（32byteのbase64である必要があります）。');
        }

        return $key;
    }
}
