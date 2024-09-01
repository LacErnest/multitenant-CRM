<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Models\LegalEntitySetting;
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

$factory->define(LegalEntitySetting::class, function (Faker $faker) {

    return [
        'quote_number'                   => 0,
        'quote_number_format'            => 'Q-[YYYY][MM]XXXX',
        'order_number'                   => 0,
        'order_number_format'            => 'O-[YYYY][MM]XXXX',
        'invoice_number'                 => 0,
        'invoice_number_format'          => 'I-[YYYY][MM]XXXX',
        'purchase_order_number'          => 0,
        'purchase_order_number_format'   => 'PO-[YYYY][MM]XXXX',
        'resource_invoice_number'        => 0,
        'resource_invoice_number_format' => 'RI-[YYYY][MM]XXXX',
        'invoice_payment_number'        => 0,
        'invoice_payment_number_format' => 'P-[YYYY][MM]XXXX',
    ];
});

$factory->afterCreating(App\Models\LegalEntitySetting::class, function ($setting) {
    //
});
