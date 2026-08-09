<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ファイル機能のE2E暗号化で使うvault鍵ペア（secret_vault_keys）。
 *
 * 全行が「同一のP-256鍵ペア」を指し、秘密鍵のラップ手段（認証器 or リカバリコード）だけが行ごとに違う。
 * したがって public_key はどの行から取っても同じ値になる。
 */
class SecretVaultKeyModel extends Model
{
    protected $table = 'secret_vault_keys';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ファイル鍵のラップに使う公開鍵（SPKI DERの生バイト列）を返す。
     * vaultが未登録の場合はnullを返す（呼び出し側でアップロードを止めること）。
     */
    public static function publicKeyRaw(): ?string
    {
        $encoded = static::query()->value('public_key');

        if (! $encoded) {
            return null;
        }

        $der = base64_decode($encoded, true);

        return $der === false ? null : $der;
    }
}
