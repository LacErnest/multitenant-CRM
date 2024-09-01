<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Models\Setting;

class EloquentSettingRepository extends EloquentRepository implements SettingRepositoryInterface
{
    public const CURRENT_MODEL = Setting::class;
}
