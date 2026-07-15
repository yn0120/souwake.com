<?php

namespace App\Http\Middleware\Secrets;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNoUserSecrets
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $admin = DB::table('admins')
            ->where('id', Auth::id())
            ->whereNotNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $admin) {
            Auth::logout();
            $request->session()->flush();
            Log::info('INFO RedirectIfNoUserSecretsミドルウェア '.__METHOD__.'#'.__LINE__.' >>> [有効中]の管理者がない');

            return redirect()->route('officeSecretsLoginInput');
        }

        return $next($request);
    }
}
