<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ログイン中の管理者の最終アクティビティ日時を更新するミドルウェア。
 * 秘密ファイル機能の「最終操作から7日で抹消」判定に使う（admins.last_activity_at）。
 * admin.souwake.com・office.souwake.com双方の認証済みルートに適用する。
 */
class TouchAdminActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            DB::table('admins')->where('id', Auth::id())->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
