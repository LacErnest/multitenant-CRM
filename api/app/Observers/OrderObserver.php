<?php

namespace App\Observers;

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\Order;
use App\Services\ItemService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class OrderObserver
{
    public function updated(Order $order)
    {
        if ($order->isDirty('currency_code')) {
            $currencyRates = getCurrencyRates();
            $customerCurrency = CurrencyCode::make($order->currency_code)->__toString();
            if (Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
                $order->currency_rate_customer = (1 /$currencyRates['rates']['USD']) * $currencyRates['rates'][$customerCurrency];
            } else {
                $order->currency_rate_customer = $currencyRates['rates'][$customerCurrency];
            }
            $order->saveQuietly();

            if ($order->manual_input) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $order->id, Order::class);
            }
        }

        if ($order->isDirty('vat_status') || $order->isDirty('vat_percentage')) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $order->id, Order::class);
        }
    }
}
