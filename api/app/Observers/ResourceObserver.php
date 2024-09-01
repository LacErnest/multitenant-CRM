<?php

namespace App\Observers;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\XeroEntityStorageRepositoryInterface;
use App\Jobs\XeroUpdate;
use App\Models\Resource;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use App\Services\XeroEntityStorageService;
use Exception;
use Illuminate\Support\Facades\App;

class ResourceObserver
{
    public function created(Resource $resource)
    {
        $id = getTenantWithConnection();
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByIdOrNull($resource->legal_entity_id);

        if ($legalEntity && (new XeroAuthService($legalEntity))->exists()) {
            try {
                XeroUpdate::dispatch($id, 'created', Resource::class, $resource->id, $legalEntity->id)->onQueue('low');
            } catch (Exception $exception) {
            }
        }
    }


    public function updated(Resource $resource)
    {
        $id = getTenantWithConnection();
        $xeroStorageRepository = App::make(XeroEntityStorageRepositoryInterface::class);
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);

        $xeroLinkResources = $xeroStorageRepository->getAllBy('document_id', $resource->id);

        foreach ($xeroLinkResources as $xeroLinkResource) {
            $legalEntity = $legalEntityRepository->firstByIdOrNull($xeroLinkResource->legal_entity_id);
            if ($legalEntity && (new XeroAuthService($legalEntity))->exists()) {
                try {
                    XeroUpdate::dispatch($id, 'updated', Resource::class, $resource->id, $legalEntity->id)->onQueue('low');
                } catch (Exception $exception) {
                    logger($exception);
                }
            }
        }
    }

    public function deleted(Resource $resource)
    {
        $id = getTenantWithConnection();
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByIdOrNull($resource->legal_entity_id);

        if ($legalEntity && (new XeroAuthService($legalEntity))->exists()) {
            try {
                XeroUpdate::dispatch($id, 'deleted', Resource::class, $resource->id)->onQueue('low');
            } catch (Exception $exception) {
            }
        }
    }
}
