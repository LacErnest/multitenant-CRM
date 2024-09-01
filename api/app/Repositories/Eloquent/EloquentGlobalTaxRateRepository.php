<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Models\GlobalTaxRate;
use Carbon\Carbon;

class EloquentGlobalTaxRateRepository extends EloquentRepository implements GlobalTaxRateRepositoryInterface
{
    public const CURRENT_MODEL = GlobalTaxRate::class;

    protected GlobalTaxRate $globalTaxRate;

    public function __construct(GlobalTaxRate $globalTaxRate)
    {
        $this->globalTaxRate = $globalTaxRate;
    }

    public function getTaxRateForPeriod(?string $legalEntityId, Carbon $date)
    {
        return $this->globalTaxRate->where('legal_entity_id', $legalEntityId)->whereDate('start_date', '<=', $date)
        ->where(function ($query) use ($date) {
            $query->whereDate('end_date', '>=', $date)
              ->orWhere('end_date', null);
        })->first();
    }
}
