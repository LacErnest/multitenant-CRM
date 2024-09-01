<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;

class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public const CURRENT_MODEL = PurchaseOrder::class;
}
