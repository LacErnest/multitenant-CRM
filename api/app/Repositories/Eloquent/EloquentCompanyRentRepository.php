<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CompanyRentRepositoryInterface;
use App\Models\CompanyRent;

class EloquentCompanyRentRepository extends EloquentRepository implements CompanyRentRepositoryInterface
{
    public const CURRENT_MODEL = CompanyRent::class;
}
