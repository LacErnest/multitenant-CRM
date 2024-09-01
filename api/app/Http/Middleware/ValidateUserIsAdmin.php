<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Validation\UnauthorizedException;

class ValidateUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user() === null) {
            return $next($request);
        } else {
            if (UserRole::isAdmin(auth()->user()->role)) {
                return $next($request);
            } else {
                throw new UnauthorizedException();
            }
        }
    }
}
