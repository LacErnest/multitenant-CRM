<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\QuoteRepositoryInterface;
use App\Models\Quote;

class EloquentQuoteRepository extends EloquentRepository implements QuoteRepositoryInterface
{
    public const CURRENT_MODEL = Quote::class;
}
