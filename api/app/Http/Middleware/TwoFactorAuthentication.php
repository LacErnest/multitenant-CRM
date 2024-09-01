<?php

namespace App\Http\Middleware;

use App\Models\Resource;
use App\Models\ResourceLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\JWTAuth;

class TwoFactorAuthentication extends BaseMiddleware
{
    protected $auth;

    public function __construct(JWTAuth $auth)
    {
        parent::__construct($auth);
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (config('auth.auth_2fa') && auth()->user() && !(auth()->user() instanceof Resource) && !auth()->user()->google2fa) {
            throw new UnauthorizedHttpException('jwt-auth', '2 Factor Authentication not activated.');
        } else {
            return $next($request);
        }
    }
}
