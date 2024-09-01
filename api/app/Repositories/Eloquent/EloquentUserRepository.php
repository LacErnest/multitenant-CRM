<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public const CURRENT_MODEL = User::class;
}
