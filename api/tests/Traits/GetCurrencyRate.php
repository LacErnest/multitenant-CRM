<?php

namespace Tests\Traits;

use App\Enums\CurrencyCode;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

trait GetCurrencyRate
{
    public function getCurrencyRate($currencyCode)
    {
        $currencyRates = getCurrencyRates()['rates'];
        if (is_string($currencyCode)) {
            return $currencyRates[$currencyCode];
        } else if (is_integer($currencyCode)) {
            return $currencyRates[CurrencyCode::make($currencyCode)->__toString()];
        }
        return $currencyRates[$currencyCode->__toString()];
    }
}
