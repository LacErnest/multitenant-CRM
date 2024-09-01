<?php

namespace App\Observers;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Jobs\XeroUpdate;
use App\Mail\PurchaseOrderAlert;
use App\Mail\PurchaseOrderAuthorised;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\ResourceLogin;
use App\Services\ItemService;
use App\Services\OrderService;
use App\Services\ResourceService;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tenancy\Facades\Tenancy;

class PurchaseOrderObserver
{
    public function created(PurchaseOrder $po)
    {
        if ($po->payment_terms < 30) {
            $company = Company::find(getTenantWithConnection());
            Mail::to($company->users()->where('role', UserRole::owner()->getIndex())->get())->queue(new PurchaseOrderAlert($company->id, $po->id));
        }
    }

    public function updated(PurchaseOrder $po)
    {
        $companyId = getTenantWithConnection();
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByIdOrNull($po->legal_entity_id);

        if ($po->isDirty('payment_terms') && $po->payment_terms < 30) {
            $company = Company::find($companyId);
            Mail::to($company->users()->where('role', UserRole::owner()->getIndex())->get())->queue(new PurchaseOrderAlert($company->id, $po->id));
        }

        if ($po->isDirty('status') && (PurchaseOrderStatus::isSubmitted($po->status))) {
            if (!$po->project->purchase_order_project) {
                $orderService = App::make(OrderService::class);
                $orderService->markUpCheck($po->project, $companyId);
            }
        }

        if ($po->isDirty('currency_code')) {
            $currencyRates = getCurrencyRates();
            $customerCurrency = CurrencyCode::make($po->currency_code)->__toString();
            if (Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()) {
                $po->currency_rate_resource = (1 /$currencyRates['rates']['USD']) * $currencyRates['rates'][$customerCurrency];
            } else {
                $po->currency_rate_resource = $currencyRates['rates'][$customerCurrency];
            }
            $po->saveQuietly();

            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates($companyId, $po->id, PurchaseOrder::class);
        }

        if ($po->isDirty('vat_status') || $po->isDirty('vat_percentage')) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates($companyId, $po->id, PurchaseOrder::class);
        }

        if ($legalEntity && (new XeroAuthService($legalEntity))->exists()) {
            try {
                if (!$po->xero_id && $po->created_at->diffInSeconds(now()) > 60) {
                    XeroUpdate::dispatch($companyId, 'created', PurchaseOrder::class, $po->id, $legalEntity->id)->onQueue('low');
                } else {
                    XeroUpdate::dispatch($companyId, 'updated', PurchaseOrder::class, $po->id, $legalEntity->id)->onQueue('low');
                }
            } catch (Exception $exception) {
                logger($exception->getMessage());
            }
        }

        if (PurchaseOrderStatus::isAuthorised($po->status)) {
            $resource = null;
            if ($po->resource_id) {
                $resourceService = App::make(ResourceService::class);
                $resource = $resourceService->findBorrowedResource($po->resource_id);
            }

            if ($resource && ResourceType::isFreelancer($resource->type)) {
                $hashKey = getHashKey();
                $token = createNewToken($hashKey);
                $hash = Hash::make($token);
                $token = base64_encode(json_encode(['resource_id' => $resource->id, 'hash' => $hash]));

                try {
                    ResourceLogin::create(['resource_id' => $resource->id, 'token' => $hash]);

                    if ($companyId != $resource->company_id) {
                        Tenancy::setTenant(Company::find($resource->company_id));
                        ResourceLogin::create(['resource_id' => $resource->id, 'token' => $hash]);
                        Tenancy::setTenant(Company::find($companyId));
                    }

                    $mailTo = [
                    [
                    'email' => $resource->email,
                    'name' => $resource->name,
                    ]
                    ];
                    Mail::to($mailTo)->queue(new PurchaseOrderAuthorised($companyId, $po->id, $token, $resource->id));
                } catch (Exception $exception) {
                    logger($exception->getMessage());
                }
            }
        }
    }
}
