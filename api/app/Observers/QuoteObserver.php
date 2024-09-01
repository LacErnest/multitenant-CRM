<?php

namespace App\Observers;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\QuoteStatus;
use App\Jobs\ElasticUpdateAssignment;
use App\Jobs\XeroUpdate;
use App\Models\Company;
use App\Models\Quote;
use App\Services\ItemService;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;


class QuoteObserver
{
    public function updated(Quote $quote)
    {
        if ($quote->isDirty('currency_code')) {
            $currencyRates = getCurrencyRates();
            $customerCurrency = CurrencyCode::make($quote->currency_code)->__toString();
            if (Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
                $quote->currency_rate_customer = (1 /$currencyRates['rates']['USD']) * $currencyRates['rates'][$customerCurrency];
            } else {
                $quote->currency_rate_customer = $currencyRates['rates'][$customerCurrency];
            }
            $quote->saveQuietly();

            if ($quote->manual_input) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $quote->id, Quote::class);
            }
        }

        if ($quote->isDirty('vat_status') || $quote->isDirty('vat_percentage')) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $quote->id, Quote::class);
        }

        if ($quote->isDirty('status')) {
            $original = $quote->getOriginal('status');
            $dirty = $quote->status;
            if ((QuoteStatus::isDraft($original) || QuoteStatus::isDeclined($original) || QuoteStatus::isInvoiced($original)) &&  !(QuoteStatus::isSent($dirty) || QuoteStatus::isCancelled(($dirty)))) {
                return;
            }

            if ((QuoteStatus::isSent($original) || QuoteStatus::isOrdered($original)) && !(QuoteStatus::isInvoiced($dirty) || QuoteStatus::isDeclined($dirty) || QuoteStatus::isCancelled(($dirty)))) {
                return;
            }
        } elseif (!QuoteStatus::isDraft($quote->status)) {
            return;
        }

        $companyId = getTenantWithConnection();
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByIdOrNull($quote->legal_entity_id);

        if (!$quote->shadow && $legalEntity && (new XeroAuthService($legalEntity))->exists()) {
            try {
                if (!$quote->xero_id && $quote->created_at->diffInSeconds(now()) > 60) {
                    XeroUpdate::dispatch($companyId, 'created', Quote::class, $quote->id, $legalEntity->id)->onQueue('low');
                } else {
                    XeroUpdate::dispatch($companyId, 'updated', Quote::class, $quote->id, $legalEntity->id)->onQueue('low');
                }
            } catch (Exception $exception) {
            }
        }
    }
}
