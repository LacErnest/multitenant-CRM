<?php

namespace Tests\Traits;

use App\Enums\CurrencyCode;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

trait EntityTrait
{
    public function sumItemPrice($items, $rate)
    {
        return $items->map(function($item) use($rate){
            return $item->quantity * round($item->service->price * $rate, 2);
        })->sum();
    }
}
