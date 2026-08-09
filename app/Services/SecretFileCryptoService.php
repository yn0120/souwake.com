<?php

namespace App\Services;

use RuntimeException;

/**
 * ファイル機能の封筒暗号化（envelope encryption）を担うサービス。
 *
 * - ファイルごとにランダムなファイル鍵を生成し、**vaultの公開鍵**でラップして保管する。
 *   ラップにはP-256のECDH-ES（一時鍵ペアを生成 → vault公開鍵とECDH → HKDF-SHA256 →
 *   AES-256-GCM）を使う。サーバーはvaultの公開鍵しか持たないため「ラップはできるが
 *   アンラップはできない」状態になり、平文がoriginからもCloudflareのエッジからも出ない
 *   （E2E暗号化）。アンラップできるのは、vault秘密鍵をWebAuthn PRF等で復号したブラウザだけ。
 * - 本文はチャンク単位（chunk_size）のAES-256-GCMで独立に暗号化・認証する（chunked AEAD）。
 *   HTTP Rangeリクエスト（動画のシーク等）でチャンク単位のランダムアクセス復号ができるようにするためで、
 *   AAD（関連データ）にファイルUUID・チャンクindex・最終チャンクフラグを含めることで
 *   チャンクの差し替え・順序入替・末尾切り詰めも検知できる。
 *   **このチャンク形式はブラウザのWebCryptoがそのまま復号できる形になっている**
 *   （AES-GCM / 12byteノンス / タグ末尾連結 / additionalData）。E2E化にあたって
 *   ディスク上の暗号文は一切作り変えていない。
 * - 抹消（crypto-shred）は、このサービスを介さずDB側のラップ済み鍵の行を削除するだけで、
 *   本文の暗号文がディスク上に残っていても計算量的に復元不能になる。
 *
 * 旧方式との違い: 以前は SECRETS_MASTER_KEY でラップし、PHP側で復号してストリーミング配信していた。
 * その方式では .env とディスクを取られた時点で復号でき、さらにCloudflareのエッジを平文が通っていた。
 */
class SecretFileCryptoService
{
    private const KEY_LEN = 32;   // AES-256
    private const NONCE_LEN = 12; // GCM推奨の96bit
    private const TAG_LEN = 16;

    /** vault鍵ペアの曲線。WebCryptoが名前付き曲線として標準対応しているP-256を使う */
    private const EC_CURVE = 'prime256v1';

    /**
     * HKDFのinfo。ブラウザ側（secrets-sw.js）と完全に一致させること。
     * 用途ごとに異なる文字列にして、同じ共有秘密から導出した鍵が他の用途に流用されないようにする。
     */
    private const HKDF_INFO_FILE_KEY = 'souwake-secrets-file-key-v1';

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
     * vaultの公開鍵でファイル鍵をラップする（P-256 ECDH-ES + HKDF-SHA256 + AES-256-GCM）。
     *
     * 手順:
     *   1. このファイル専用の一時P-256鍵ペアを生成する
     *   2. 一時秘密鍵 × vault公開鍵 のECDHで共有秘密を得る（openssl_pkey_derive）
     *   3. HKDF-SHA256で共有秘密からAES-256の鍵を導出する
     *      （saltには一時公開鍵を使い、同じvault公開鍵でも毎回異なる鍵になるようにする）
     *   4. その鍵でファイル鍵をAES-256-GCM暗号化する
     *   5. 一時「秘密」鍵はここで捨てる。以降このファイル鍵をアンラップできるのは
     *      vault秘密鍵を持つブラウザだけになる（サーバーには復号手段が残らない）
     *
     * RSA-OAEPではなくECDH-ESを選んだのは、PHPの openssl_public_encrypt が
     * OAEPのハッシュを選択できずSHA-1 MGF1固定になるのに対し、ECDH + HKDF-SHA256 なら
     * PHP（openssl_pkey_derive / hash_hkdf）とブラウザ（WebCrypto ECDH / HKDF）の
     * 双方でモダンなプリミティブのまま、追加の依存なしに実装できるため。
     *
     * @param  string  $vaultPublicKeySpki  vaultのP-256公開鍵（SPKI DERの生バイト列）
     * @return array{eph_public_key: string, wrapped_key: string, nonce: string, tag: string} 生バイト列（呼び出し側でbase64化する）
     */
    public static function wrapFileKeyForVault(string $vaultPublicKeySpki, string $fileKey): array
    {
        $vaultPublicKey = openssl_pkey_get_public(self::derToPem($vaultPublicKeySpki, 'PUBLIC KEY'));
        if ($vaultPublicKey === false) {
            throw new RuntimeException('vault公開鍵の読み込みに失敗しました。');
        }

        $ephemeral = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => self::EC_CURVE,
        ]);
        if ($ephemeral === false) {
            throw new RuntimeException('一時鍵ペアの生成に失敗しました。');
        }

        $sharedSecret = openssl_pkey_derive($vaultPublicKey, $ephemeral);
        if ($sharedSecret === false) {
            throw new RuntimeException('ECDHの共有秘密の導出に失敗しました。');
        }

        $ephPublicKeySpki = self::pemToDer(openssl_pkey_get_details($ephemeral)['key']);

        // saltに一時公開鍵を使うことで、同じvault公開鍵に対しても毎回異なるラップ鍵になる
        $wrapKey = hash_hkdf('sha256', $sharedSecret, self::KEY_LEN, self::HKDF_INFO_FILE_KEY, $ephPublicKeySpki);

        $nonce = random_bytes(self::NONCE_LEN);
        $tag = '';
        $wrapped = openssl_encrypt(
            $fileKey,
            'aes-256-gcm',
            $wrapKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LEN,
        );

        if ($wrapped === false) {
            throw new RuntimeException('ファイル鍵のラップに失敗しました。');
        }

        return [
            'eph_public_key' => $ephPublicKeySpki,
            'wrapped_key' => $wrapped,
            'nonce' => $nonce,
            'tag' => $tag,
        ];
    }

    /**
     * DER（生バイト列）をPEMに変換する。opensslのPHP関数はPEMしか受け付けないため。
     */
    private static function derToPem(string $der, string $label): string
    {
        return "-----BEGIN {$label}-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END {$label}-----\n";
    }

    /**
     * PEMからDER（生バイト列）を取り出す。ブラウザのWebCryptoはSPKI/PKCS8のDERを直接扱うため、
     * DBにはDERのbase64で保存する。
     */
    private static function pemToDer(string $pem): string
    {
        $body = preg_replace('/-----(BEGIN|END)[^-]+-----|\s+/', '', $pem);
        $der = base64_decode((string) $body, true);

        if ($der === false) {
            throw new RuntimeException('PEMからDERへの変換に失敗しました。');
        }

        return $der;
    }

    /**
     * マスターキーでファイル鍵をアンラップする。
     *
     * @deprecated E2E化により、通常の閲覧経路ではもう使わない（サーバーは復号能力を持たない）。
     *             残しているのは `secrets:migrate-to-vault` が旧方式のファイルを
     *             vault方式へ載せ替える一度きりの移行のためだけ。
     *             移行完了後は 2026_08_08_000003 のマイグレーションで対象カラムごと消え、
     *             SECRETS_MASTER_KEY も .env から削除できる。
     */
    public static function unwrapFileKey(string $wrappedKey, string $nonce, string $tag): string
    {
        return self::unwrapFileKeyUsing(self::masterKey(), $wrappedKey, $nonce, $tag);
    }

    /**
     * 指定したマスターキー（生バイト列）でファイル鍵をアンラップする。
     * `secrets:migrate-to-vault` が、現在の設定とは異なる旧マスターキーを使う場合に使う。
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
     * `secrets:migrate-to-vault` が旧マスターキーをデコードする際にも使う。
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
