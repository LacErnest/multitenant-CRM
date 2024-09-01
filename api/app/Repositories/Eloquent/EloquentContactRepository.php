<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Models\Contact;

class EloquentContactRepository extends EloquentRepository implements ContactRepositoryInterface
{
    public const CURRENT_MODEL = Contact::class;
}
