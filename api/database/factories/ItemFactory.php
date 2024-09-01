<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Item;
use App\Models\Service;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
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

$factory->define(App\Models\Item::class, function (Faker $faker, $attributes) {

  $service = null;
  $quantity = null;
  if ($attributes['entity_type'] != \App\Models\PurchaseOrder::class) {
    $service = Service::get()->random(1)->first();
    switch ($service->price_unit) {
      case 'unit':
        $quantity = $faker->numberBetween(1, 3);
        break;
      case 'day':
        $quantity = $faker->numberBetween(5, 20);
        break;
      case 'hours':
        $quantity = $faker->numberBetween(10, 50);
        break;
      default:
        $quantity = 5;
        break;
    }
  }

  $entityClass = $attributes['entity_type'];
  $model = $entityClass::find($attributes['entity_id']);


  $companyId = $attributes['company_id'] ?? getTenantWithConnection();

  if (empty($attributes['company_id']) && !empty($model) && $model->master) {
    $companyId = (mt_rand(0, 99) < 90) ? $companyId : Company::inRandomOrder()->first()->id;
  }

  return [
    'id' => Uuid::uuid(),
    'xero_id' => Uuid::uuid(),
    'entity_id' => $attributes['entity_id'],
    'entity_type' => $attributes['entity_type'],
    'service_id' => $service->id ?? null,
    'company_id' => $companyId,
    'service_name' => $service->name ?? null,
    'description' => $faker->sentence($nbWords = 6, $variableNbWords = true),
    'quantity' => $quantity ? $quantity : $faker->numberBetween(1, 20),
    'unit' => $service ? $service->price_unit : array_rand(['unit' => 'unit', 'hours' => 'hours', 'days' => 'days']),
    'unit_price' => $service ? $service->price : $faker->randomFloat($nbMaxDecimals = 2, $min = 40, $max = 500),
    'order' => Item::where('entity_id', $attributes['entity_id'])->count(),
    'exclude_from_price_modifiers' => false
  ];
});
