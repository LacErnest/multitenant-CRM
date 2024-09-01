<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\BankRepositoryInterface;
use App\Models\Bank;

class EloquentBankRepository extends EloquentRepository implements BankRepositoryInterface
{
    public const CURRENT_MODEL = Bank::class;
}
