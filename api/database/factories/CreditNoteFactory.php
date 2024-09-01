<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Invoice;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\CurrencyCode;
use App\Enums\CreditNoteStatus;
use App\Enums\CreditNoteType;
/*
|--------------------------------------------------------------------------
| Model Factoriescheclsea grin
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\Models\CreditNote::class, function (Faker $faker, $attributes) {

    $statuses = CreditNoteStatus::getIndices();
    $types = CreditNoteType::getIndices();

    static $statusCount = 0;
    static $typeCount = 0;

  if ($statusCount > count($statuses) - 1) {
      $statusCount = 0;
  }

  if ($typeCount > count($types) - 1) {
      $typeCount = 0;
  }
    $invoice = Invoice::findOrFail($attributes['invoice_id']);

    return [
        'xero_id' => Uuid::uuid(),
        'project_id' => $attributes['project_id'],
        'invoice_id' => $attributes['invoice_id'],
        'status' => $statuses[$statusCount++],
        'type' => $attributes['type'],
        'reference' => $faker->text(100),
        'number' => $faker->numberBetween(),
        'currency_code' => $attributes['currency_code'],
        'date' => $attributes['date'],
        'total_price' => $invoice->total_price / 40,
        'total_vat' => $invoice->total_vat / 40,
        'total_price_usd' => $invoice->total_price_usd / 40,
        'total_vat_usd' => $invoice->total_vat_usd / 40,
    ];
});
