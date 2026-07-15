<?php

namespace App\Http\Middleware\Secrets;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * ファイル機能(/secrets, /secrets/upload)にアクセスできるのは
 * config('secrets.admin_id')（既定admins.id=1）の管理者のみ。
 *
 * 既存のOfficeパネルの「権限なし→officeTopへリダイレクト+フラッシュ」という規約とは
 * 意図的に異なり、要件通り404を返す（ページの存在自体を教えないため）。
 */
class EnsureSecretsAdmin
{
    public function handle($request, Closure $next)
    {
        if (! Auth::check() || Auth::id() !== (int) config('secrets.admin_id')) {
            abort(404);
        }

        return $next($request);
    }
}
