<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Models\LegalEntitySetting;

class EloquentLegalEntitySettingRepository extends EloquentRepository implements LegalEntitySettingRepositoryInterface
{
    public const CURRENT_MODEL = LegalEntitySetting::class;
}
