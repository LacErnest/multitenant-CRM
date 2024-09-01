<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CompanyLoanRepositoryInterface;
use App\Models\CompanyLoan;

class EloquentCompanyLoanRepository extends EloquentRepository implements CompanyLoanRepositoryInterface
{
    public const CURRENT_MODEL = CompanyLoan::class;
}
