<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SmtpSettingRepositoryInterface;
use App\Models\SmtpSetting;


class EloquentSmtpSettingRepository extends EloquentRepository implements SmtpSettingRepositoryInterface
{
    public const CURRENT_MODEL = SmtpSetting::class;
}
