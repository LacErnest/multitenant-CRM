<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CompanyNotificationSettingRepositoryInterface;
use App\Models\CompanyNotificationSetting;


class EloquentCompanyNotificationSettingRepository extends EloquentRepository implements CompanyNotificationSettingRepositoryInterface
{
    public const CURRENT_MODEL = CompanyNotificationSetting::class;
}
