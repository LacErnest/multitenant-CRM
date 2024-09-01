<?php

namespace App\Http\Middleware;

use App\Services\Xero\EloquentEntityStore;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

/**
 * Class XeroToCompanyBinding
 *
 * Binds current company to XERO auth implementation
 */
class XeroToCompanyBinding
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $companyId = $request->route('company_id');

        App::bind(OauthCredentialManager::class, function (Application $app) use ($companyId) {
            return new EloquentEntityStore($companyId, $app->make(Oauth2Provider::class));
        });

        return $next($request);
    }
}
