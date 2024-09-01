<?php

/** @var Factory $factory */

use App\Models\Setting;
use App\Repositories\TemplateRepository;
use Faker\Generator as Faker;
use \Faker\Provider\Uuid;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factory;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/




$factory->define(Setting::class, function (Faker $faker) {
    $orderCount = Order::count();
    $quoteCount = Quote::count();
    $invoiceCount = Invoice::count();
    $purchaseOrderCount = PurchaseOrder::count();
    $paymentCount = InvoicePayment::count();
    return [
        'id' => Uuid::uuid(),
        'quote_number' => $quoteCount,
        'quote_number_format' => 'Q-YYYYMMXXXX',
        'quote_template' => null,
        'order_number' => $orderCount,
        'order_number_format' => 'O-YYYYMMXXXX',
        'order_template' => null,
        'invoice_number' => $invoiceCount,
        'invoice_number_format' => 'I-YYYYMMXXXX',
        'invoice_template' => null,
        'purchase_order_number' => $purchaseOrderCount,
        'purchase_order_number_format' => 'PO-YYYYMMXXXX',
        'purchase_order_template' => null,
        'invoice_payment_number_format' => 'P-YYYYMMXXXX',
        'invoice_payment_number' => $paymentCount,
    ];
});
