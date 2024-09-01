<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\InvoicePaymentRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Item;
use App\Models\MasterShadow;
use App\Models\Order;
use App\Services\ItemService;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;

class EloquentInvoicePaymentRepository extends EloquentRepository implements InvoicePaymentRepositoryInterface
{
    public const CURRENT_MODEL = InvoicePayment::class;
}
