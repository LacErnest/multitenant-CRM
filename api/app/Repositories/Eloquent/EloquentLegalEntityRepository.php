<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\LegalEntity;

class EloquentLegalEntityRepository extends EloquentRepository implements LegalEntityRepositoryInterface
{
    public const CURRENT_MODEL = LegalEntity::class;
}
