<?php

namespace App\Http\Middleware;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

/**
 * Class LegalEntityIdentification
 * Middleware to check if legal entity exists
 *
 */
class LegalEntityIdentification
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
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByid($request->route('legal_entity_id'));

        if ($legalEntity) {
            return $next($request);
        } else {
            throw new ModelNotFoundException();
        }
    }
}
