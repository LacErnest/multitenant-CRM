<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;

class AccessMiddleware
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
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$permissions
     * @return mixed
     * @throws Exception
     */
    public function handle($request, Closure $next, ...$permissions)
    {
        if ($this->auth->check()) {
            /** @var User $user */
            $user = $this->auth->user();

            if ($user->disabled_at) {
                throw new UnauthorizedException('This account is disabled', Response::HTTP_FORBIDDEN);
            }

            foreach ($permissions as $permission) {
                if (($user->role == $permission && $permission != UserRole::super_admin()->getIndex())
                || ($permission == UserRole::super_admin()->getIndex() && $user->super_user)
                ) {
                    return $next($request);
                }
            }
        }

        throw new UnauthorizedException('unwarranted', Response::HTTP_FORBIDDEN);
    }
}
