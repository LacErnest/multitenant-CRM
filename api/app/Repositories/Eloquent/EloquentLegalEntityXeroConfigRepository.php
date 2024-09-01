<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LegalEntityXeroConfigRepositoryInterface;
use App\Models\LegalEntityXeroConfig;

class EloquentLegalEntityXeroConfigRepository extends EloquentRepository implements LegalEntityXeroConfigRepositoryInterface
{
    public const CURRENT_MODEL = LegalEntityXeroConfig::class;
}
