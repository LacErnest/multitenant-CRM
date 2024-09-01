<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CustomerAddressRepositoryInterface;
use App\Models\CustomerAddress;

class EloquentCustomerAddressRepository extends EloquentRepository implements CustomerAddressRepositoryInterface
{
    public const CURRENT_MODEL = CustomerAddress::class;
}
