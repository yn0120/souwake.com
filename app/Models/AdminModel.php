<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminModel extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admins';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * ログインユーザーが操作可能な権限を返す
     *
     * @return HasMany
     */
    public function routes()
    {
        return $this->hasManyThrough(
            RouteModel::class,
            RoleRouteModel::class,
            'role_id',
            'id',
            'role_id',
            'route_id',
        )->whereNull('routes.deleted_at')
            ->whereNull('role_route.deleted_at')
            ->where('role_route.is_allowed', 1)
            ->pluck('routes.sys_name', 'routes.id')
            ->toArray();
    }

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
