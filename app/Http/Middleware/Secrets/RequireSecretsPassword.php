<?php

namespace App\Http\Middleware\Secrets;

use Closure;

class RequireSecretsPassword
{
    public function handle($request, Closure $next)
    {
        if (session('secrets_password_verified') === true) {
            return $next($request);
        }

        return redirect()->route('officeSecretsPasswordInput');
    }
}
