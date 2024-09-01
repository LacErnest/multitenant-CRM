<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Validation\UnauthorizedException;

class IsNotSalesPerson
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
            if (UserRole::isSales(auth()->user()->role)) {
                throw new UnauthorizedException();
            } else {
                return $next($request);
            }
        }
    }
}
