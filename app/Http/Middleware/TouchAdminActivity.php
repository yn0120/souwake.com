<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ログイン中の管理者の最終アクティビティ日時を更新するミドルウェア。
 * ファイル機能の「最終操作から7日で抹消」判定に使う（admins.last_activity_at）。
 * admin.souwake.com・office.souwake.com双方の認証済みルートに適用する。
 *
 * office.souwake.com（ファイル機能）だけに限定していないのは意図的である。
 * この日時が答えるべき問いは「持ち主が secrets ページを開いたか」ではなく
 * 「持ち主がシステムのどこかで生きているか」であり、admin側を日常的に使っていれば
 * 生存は明らかだからである。office限定にすると、7日間 office を開かなかっただけで
 * 全ファイルが復元不能に抹消されてしまう（OfficeAuthControllerのログイン・ログアウト時にも
 * 同じ意図で last_activity_at を記録している）。
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
