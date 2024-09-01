<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Validation\UnauthorizedException;

class TaxRatesValidation
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
            $isAdmin = UserRole::isAdmin(auth()->user()->role);
            $isOwner = UserRole::isOwner(auth()->user()->role);
            $isAccountant = UserRole::isAccountant(auth()->user()->role);

            if ($isAdmin || $isOwner || $isAccountant) {
                return $next($request);
            }

            throw new UnauthorizedException();
        }
    }
}
