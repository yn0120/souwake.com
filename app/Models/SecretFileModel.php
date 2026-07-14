<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretFileModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'secret_files';

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
     * 指定された条件でレコードを返す
     *
     * @param  array  $params
     *                         - id: int 指定されたidで絞り込み
     *                         - uuid: string 指定されたuuidで絞り込み
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
            ->when(isset($params['status']) && $params['status'], function ($query) use ($params) {
                return is_array($params['status'])
                    ? $query->whereIn('status', $params['status'])
                    : $query->where('status', $params['status']);
            })
            ->orderByRaw('id DESC');

        if (isset($params['method']) && $params['method']) {
            return $builder->{$params['method']}();
        }

        return $builder->get();
    }
}
