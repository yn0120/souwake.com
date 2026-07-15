<?php

namespace App\Http\Middleware\Secrets;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticatedSecrets
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            return redirect()->route('officeSecretsIndex');
        }

        return $next($request);
    }
}
