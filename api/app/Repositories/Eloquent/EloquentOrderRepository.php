<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;

class EloquentOrderRepository extends EloquentRepository implements OrderRepositoryInterface
{
    public const CURRENT_MODEL = Order::class;
}
