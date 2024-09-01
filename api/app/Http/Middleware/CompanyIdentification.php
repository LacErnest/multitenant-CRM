<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Resource;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompanyIdentification
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
        $user = auth()->user();

        if (auth()->user() === null) {
            return $next($request);
        } else {
            $userIsResource = $user instanceof Resource;
            $companyUser = $userIsResource ? null : User::where([
            ['email', $user->email],
            ['company_id', $request->route('company_id')]
            ])->first();
            $userIsAdmin = $user->role === UserRole::admin()->getIndex();

            if ($companyUser || $userIsAdmin || $userIsResource) {
                if (!$userIsResource && !$userIsAdmin) {
                    $user->role = $companyUser->role;
                }

                return $next($request);
            } else {
                throw new ModelNotFoundException();
            }
        }
    }
}
