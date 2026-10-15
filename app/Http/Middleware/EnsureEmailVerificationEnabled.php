<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureEmailVerificationEnabled extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! config('auth.verify_email')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
