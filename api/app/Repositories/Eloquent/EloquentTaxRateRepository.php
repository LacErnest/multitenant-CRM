<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TaxRateRepositoryInterface;
use App\Models\TaxRate;

class EloquentTaxRateRepository extends EloquentRepository implements TaxRateRepositoryInterface
{
    public const CURRENT_MODEL = TaxRate::class;
}
