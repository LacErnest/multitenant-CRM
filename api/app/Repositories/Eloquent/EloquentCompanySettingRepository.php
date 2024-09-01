<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CompanySettingRepositoryInterface;
use App\Models\CompanySetting;

class EloquentCompanySettingRepository extends EloquentRepository implements CompanySettingRepositoryInterface
{
    public const CURRENT_MODEL = CompanySetting::class;
}
