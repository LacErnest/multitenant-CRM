<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\DesignTemplateRepositoryInterface;
use App\Models\DesignTemplate;


class EloquentDesignTemplateRepository extends EloquentRepository implements DesignTemplateRepositoryInterface
{
    public const CURRENT_MODEL = DesignTemplate::class;
}
