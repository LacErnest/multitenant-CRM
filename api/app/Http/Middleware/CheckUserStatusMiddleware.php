<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckUserStatusMiddleware
{
    /**
     * The Guard implementation.
     *
     * @var Guard $auth
     */
    protected Guard $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $this->auth->user();

        if (!empty($user) && $user->disabled_at) {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Your account has been disabled'], 401);
        }

        return $next($request);
    }
}
