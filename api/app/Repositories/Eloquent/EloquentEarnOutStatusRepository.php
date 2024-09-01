<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\EarnOutStatusRepositoryInterface;
use App\Models\EarnOutStatus;

class EloquentEarnOutStatusRepository extends EloquentRepository implements EarnOutStatusRepositoryInterface
{
    public const CURRENT_MODEL = EarnOutStatus::class;
}
