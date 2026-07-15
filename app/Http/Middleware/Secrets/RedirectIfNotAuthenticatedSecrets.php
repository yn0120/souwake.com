<?php

namespace App\Http\Middleware\Secrets;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAuthenticatedSecrets
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('officeSecretsLoginInput');
        }

        return $next($request);
    }
}
