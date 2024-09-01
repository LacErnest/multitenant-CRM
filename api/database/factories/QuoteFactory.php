<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Item;
use App\Models\LegalEntitySetting;
use App\Models\Quote;
use App\Services\ItemService;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\QuoteStatus;
use App\Enums\VatStatus;
use Illuminate\Support\Facades\App;

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

$factory->define(Quote::class, function (Faker $faker, $attributes) {

    $reason = null;
    $company = Company::find(getTenantWithConnection());
    $legalEntity = $company->legalEntities()->get()->random(1)->first();
    $format = LegalEntitySetting::where('id', $legalEntity->legal_entity_setting_id)->first();
    $format->quote_number += 1;
    $format->save();

  if (QuoteStatus::isDeclined($attributes['status'])) {
      $reason = $faker->text(50);
  }

    return [
        'xero_id' => Uuid::uuid(),
        'project_id' => $attributes['project_id'],
        'reason_of_refusal' => $reason,
        'status' => $attributes['status'],
        'reference' => $faker->text(50),
        'number' => transformFormat($format->quote_number_format, $format->quote_number),
        'expiry_date' => $faker->dateTimeInInterval($startDate = $attributes['date'], $interval = '+ 30 days', $timezone = null),
        'date' => $attributes['date'],
        'legal_entity_id' => $legalEntity->id,
        'currency_code' => $attributes['currency_code'],
        'master' => Company::count() < 2 || (mt_rand(0, 99) < 90) ? false : true,
        'vat_status' => VatStatus::never()->getIndex(),
    ];
});

$factory->afterCreating(Quote::class, function ($quote) {
    $company = Company::find(getTenantWithConnection());
    $total = 0;

  if ($company->currency_code == \App\Enums\CurrencyCode::EUR()->getIndex()) {
      $max = 150000;
  } else {
      $max = 180000;
  }

  while ($total < $max) {
      $item = factory(Item::class)->create(['entity_id' => $quote->id, 'entity_type' => Quote::class]);
      $total += $item->unit_price * $item->quantity;
  }

    $itemService = App::make(ItemService::class);
  if (!empty($company->id)) {
      $itemService->savePricesAndCurrencyRates($company->id, $quote->id, Quote::class);
  }
});
