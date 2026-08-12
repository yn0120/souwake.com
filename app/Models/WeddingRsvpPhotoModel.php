<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingRsvpPhotoModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wedding_rsvp_photos';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 紐づく出欠回答
     */
    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(WeddingRsvpModel::class, 'wedding_rsvp_id');
    }

    /**
     * 指定された条件でレコードを返す
     *
     * @param  array  $params
     *                         - id: int 指定されたidで絞り込み
     *                         - uuid: string 指定されたuuidで絞り込み
     *                         - uuids: array 指定されたuuidの配列で絞り込み
     *                         - session_token: string 指定されたアップロード元トークンで絞り込み
     *                         - wedding_rsvp_id: int 指定された回答IDで絞り込み
     *                         - unattached: bool trueで回答未紐づけ（送信前）のみに絞り込み
     *                         - status: string|array 指定されたstatusで絞り込み
     *                         - method: string 取得方法 'first', 'get', 'count' (デフォルトは'get')
     * @return mixed
     */
    public static function getBy($params = [])
    {
        $builder = self::when(isset($params['id']) && $params['id'], function ($query) use ($params) {
            return $query->where('id', $params['id']);
        })
            ->when(isset($params['uuid']) && $params['uuid'], function ($query) use ($params) {
                return $query->where('uuid', $params['uuid']);
            })
            ->when(isset($params['uuids']) && $params['uuids'], function ($query) use ($params) {
                return $query->whereIn('uuid', $params['uuids']);
            })
            ->when(isset($params['session_token']) && $params['session_token'], function ($query) use ($params) {
                return $query->where('session_token', $params['session_token']);
            })
            ->when(isset($params['wedding_rsvp_id']) && $params['wedding_rsvp_id'], function ($query) use ($params) {
                return $query->where('wedding_rsvp_id', $params['wedding_rsvp_id']);
            })
            ->when(isset($params['unattached']) && $params['unattached'], function ($query) {
                return $query->whereNull('wedding_rsvp_id');
            })
            ->when(isset($params['status']) && $params['status'], function ($query) use ($params) {
                return is_array($params['status'])
                    ? $query->whereIn('status', $params['status'])
                    : $query->where('status', $params['status']);
            })
            ->orderByRaw('id ASC');

        if (isset($params['method']) && $params['method']) {
            return $builder->{$params['method']}();
        }

        return $builder->get();
    }
}
