<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'maintenances';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * IPアドレスの配列を文字列に変換
     *
     * @return string
     */
    public function setAllowedIpsAttribute($value)
    {
        $this->attributes['allowed_ips'] = is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * IPアドレスの文字列を配列に変換
     *
     * @return array
     */
    public function getAllowedIpsAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    /**
     * 特定のIPアドレスが許可されているか確認
     *
     * @return bool
     */
    public function isIpAllowed($ip)
    {
        return in_array($ip, $this->allowed_ips);
    }
}
