<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\EmployeeHistoryRepositoryInterface;
use App\Models\EmployeeHistory;

class EloquentEmployeeHistoryRepository extends EloquentRepository implements EmployeeHistoryRepositoryInterface
{
    public const CURRENT_MODEL = EmployeeHistory::class;
}
