<?php

/** @var Factory $factory */

use App\Models\Company;
use App\Models\Item;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\PurchaseOrder;
use App\Services\ItemService;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\App;

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

$factory->define(PurchaseOrder::class, function (Faker $faker, $attributes) {

    $statusArray = [PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
        PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()];
    $key = array_rand($statusArray);
    $status = $statusArray[$key];
    $reason =  null;
    $rating = null;
    $payDate = null;
    $authorisedDate = null;
    $date = $faker->dateTimeInInterval($startDate = $attributes['date'], $interval = '+ 17 days', $timezone = null);
    $deliveryDate = $faker->dateTimeInInterval($startDate = $date, $interval = '+ 37 days', $timezone = null);

    if (PurchaseOrderStatus::isAuthorised($status) ||
        PurchaseOrderStatus::isBilled($status) ||
        PurchaseOrderStatus::isPaid($status)) {
        $reason = $faker->text(50);
        $rating = $faker->numberBetween(1, 5);
        $authorisedDate = $date;
    }

    if (PurchaseOrderStatus::isPaid($status)) {
        $payDate = $attributes['pay_date'] ?? $faker->dateTimeInInterval($startDate = $deliveryDate, $interval = '+ 30 days', $timezone = null);
    }

    $legalEntity = LegalEntity::find($attributes['legal_entity_id']);
    $format = LegalEntitySetting::where('id', $legalEntity->legal_entity_setting_id)->first();
    $format->purchase_order_number += 1;
    $format->save();

    return [
        'xero_id' => Uuid::uuid(),
        'project_id' => $attributes['project_id'],
        'resource_id' => $attributes['resource_id'],
        'status' => $status,
        'reference' => $faker->text(50),
        'number' => transformFormat($format->purchase_order_number_format, $format->purchase_order_number),
        'date' => $date,
        'authorised_date' => $authorisedDate,
        'delivery_date' => $deliveryDate,
        'pay_date' => $payDate,
        'rating' => $rating,
        'reason' => $reason,
        'legal_entity_id' => $legalEntity->id,
        'reason_of_rejection' => $status == PurchaseOrderStatus::rejected()->getIndex() ? $faker->text(50) : null,
        'currency_code' => $attributes['currency_code'],
    ];
});

$factory->afterCreating(PurchaseOrder::class, function ($po) {
    $company = Company::find(getTenantWithConnection());
    $total = 0;

  if ($company->currency_code == \App\Enums\CurrencyCode::EUR()->getIndex()) {
      $max = 68000;
  } else {
      $max = 80000;
  }

  while ($total < $max) {
      $item = factory(Item::class)->create(['entity_id' => $po->id, 'entity_type' => PurchaseOrder::class]);
      $total += $item->unit_price * $item->quantity;
  }

    $itemService = App::make(ItemService::class);
    $itemService->savePricesAndCurrencyRates($company->id, $po->id, PurchaseOrder::class);
});
