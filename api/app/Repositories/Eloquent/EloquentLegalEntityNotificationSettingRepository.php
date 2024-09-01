<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LegalEntityNotificationSettingRepositoryInterface;
use App\Models\LegalEntityNotificationSetting;


class EloquentLegalEntityNotificationSettingRepository extends EloquentRepository implements LegalEntityNotificationSettingRepositoryInterface
{
    public const CURRENT_MODEL = LegalEntityNotificationSetting::class;
}
