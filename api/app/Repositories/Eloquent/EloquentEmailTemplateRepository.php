<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\EmailTemplateRepositoryInterface;
use App\Models\EmailTemplate;


class EloquentEmailTemplateRepository extends EloquentRepository implements EmailTemplateRepositoryInterface
{
    public const CURRENT_MODEL = EmailTemplate::class;
}
