<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'routes';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * 指定された条件でレコードを返す
     *
     * @param  array  $params
     *                         - id: int 指定されたidで絞り込み
     *                         - ids: array 指定されたid配列で絞り込み
     *                         - withDeleted: bool true=削除済も含む (デフォルトはfalse)
     *                         - method: string 取得方法 'first', 'get', 'pluck', 'count' (デフォルトは'get')
     *                         - pluckKey: string pluckで取得する際のキー (methodが'pluck'のときに必要、デフォルトは'id')
     *                         - pluckValue: string pluckで取得する際の値 (methodが'pluck'のときに必要、デフォルトは'name')
     * @return mixed
     */
    public static function getBy($params = [])
    {
        $builder = self::when(isset($params['id']) && $params['id'], function ($query) use ($params) {
            return $query->where('id', $params['id']);
        })
            ->when(isset($params['ids']) && $params['ids'], function ($query) use ($params) {
                return $query->whereIn('id', $params['ids']);
            })
            ->when(! isset($params['withDeleted']), function ($query) {
                return $query->whereNull('deleted_at');
            })
            ->orderByRaw('id ASC');

        if (isset($params['method']) && $params['method']) {
            switch ($params['method']) {
                case 'pluck':
                    return $builder->pluck($params['pluckValue'] ?? 'name', $params['pluckKey'] ?? 'id')->toArray();

                default:
                    return $builder->{$params['method']}();
            }
        }

        return $builder->get();
    }
}
