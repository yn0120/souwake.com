<?php

namespace App\Http\Middleware\Office;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class CheckRoutePermission
{
    public function handle($request, Closure $next)
    {
        $roleBuilder = DB::table('roles')->whereNull('deleted_at');
        $roleIds = $roleBuilder->clone()->pluck('id')->toArray();

        // ログインユーザーの権限を取得
        $userRoleId = $roleBuilder->clone()->where('id', Auth::user()->role_id)->value('id');
        if (! $userRoleId) {
            Auth::logout();
            $request->session()->flush();

            return redirect()->route('officeLoginInput');
        }

        // CheckRoutePermissionミドルウェアを使っているすべてのRouteNameとdescriptionを取得
        $routePatterns = collect(Route::getRoutes())
            ->filter(function ($route) {
                return collect($route->middleware())->contains(function ($middleware) {
                    return str_contains($middleware, CheckRoutePermission::class);
                });
            })
            ->mapWithKeys(function ($route) {
                $routeName = $route->getName();
                $baseRouteName = preg_match('/(?:Input|Confirm|Execute|Complete)$/', $routeName) && isset($route->defaults['description'])
                    ? preg_replace('/(?:Input|Confirm|Execute|Complete)$/', '*', $routeName) // 入力ページにアクセス可能な人は確認処理完了も操作可能なためパターンマッチはワイルドカードでまとめる
                    : $routeName;

                return [$baseRouteName => $route->defaults['description'] ?? null];
            })
            ->filter() // null, false, '' などのfalsyな値を除外（routeNameを定義していないルートを除外）
            ->filter(fn ($_, $key) => $key && $key !== 'officeTop') // トップページは全員アクセス可能なため除外
            ->toArray();

        // DBに登録されているRouteNameを取得
        $routeNames = DB::table('routes')->whereNull('deleted_at')->pluck('sys_name', 'id')->toArray();

        // RouteNameのパターンがDBに存在しない場合は新規登録
        $notFoundRouteNames = array_diff(array_flip($routePatterns), $routeNames);
        if ($notFoundRouteNames) {
            foreach ($notFoundRouteNames as $name => $sysName) {
                // 新しくRouteNameを登録
                $routeId = DB::table('routes')->insertGetId(['name' => $name, 'sys_name' => $sysName]);

                // すべての権限に操作許可を付与
                $insert = [];
                foreach ($roleIds as $roleId) {
                    $insert[] = [
                        'route_id' => $routeId,
                        'role_id' => $roleId,
                    ];
                }
                DB::table('role_route')->insert($insert);
            }

            // 新規登録があったら取得し直す
            $routeNames = DB::table('routes')->whereNull('deleted_at')->pluck('sys_name', 'id')->toArray();
        }

        // 既存の権限を持つrole_idを取得
        $existingRoleIds = DB::table('role_route')->whereIn('role_id', $roleIds)->pluck('role_id')->unique()->toArray();

        // 権限が付与されていないrole_idを抽出
        $roleIdsToInsert = array_diff($roleIds, $existingRoleIds);

        // 新規登録データの作成
        $insert = collect($roleIdsToInsert)
            ->crossJoin(array_keys($routeNames))
            ->map(fn ($item) => [
                'role_id' => $item[0],
                'route_id' => $item[1],
            ])
            ->toArray();
        if ($insert) {
            DB::table('role_route')->insert($insert);
        }

        // 処理するRouteNameのIDを取得
        $currentRouteName = Route::currentRouteName();
        if ($currentRouteName == 'officeTop') {
            return $next($request);
        }

        $currentRoutePattern = preg_replace('/(?:Input|Confirm|Execute|Complete)$/', '*', $currentRouteName);
        $routeId = array_search($currentRoutePattern, $routeNames);

        // 操作権限をチェック
        $isAllowed = DB::table('role_route')->where('role_id', $userRoleId)->where('route_id', $routeId)->value('is_allowed');
        if ($isAllowed) {
            return $next($request);
        }

        return redirect()->route('officeTop')->with('error', '権限がありません。');
    }
}
