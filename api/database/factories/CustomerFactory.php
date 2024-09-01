<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\IndustryType;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\CustomerStatus;
use App\Enums\CurrencyCode;
use App\Models\Customer;
use Illuminate\Support\Str;

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

$factory->define(Customer::class, function (Faker $faker, $attributes) {

    $statuses = CustomerStatus::getIndices();
    static $count = 0;

  if ($count > count($statuses) - 1) {
      $count = 0;
  }

    $email = $faker->unique()->email;

  if ($user = Customer::where('email', $email)->first()) {
      $email = Str::random(5).$email;
  }

    return [
        'id' => Uuid::uuid(),
        'xero_id' => Uuid::uuid(),
        'company_id' => $attributes['company_id'],
        'status' => $statuses[$count++],
        'name' => $faker->name,
        'description' => $faker->sentence($nbWords = 10, $variableNbWords = true),
        'industry' => array_rand(IndustryType::getIndices()),
        'email' => $email,
        'tax_number' => $faker->randomNumber(),
        'default_currency' => array_rand(CurrencyCode::getIndices()),
        'website' => $faker->url,
        'phone_number' => $faker->phoneNumber,
        'billing_address_id' => function () {
            return factory(App\Models\CustomerAddress::class)->create()->id;
        },
        'operational_address_id' => function () {
            return factory(App\Models\CustomerAddress::class)->create()->id;
        },
        'average_collection_period' => $faker->numberBetween($min = 0, $max = 50),
        'legacy_customer' => mt_rand(0, 100) < 90
    ];
});

$factory->afterCreating(App\Models\Customer::class, function ($customer) {
  if ($customer->legacy_customer) {
      $customer->legacyCompanies()->attach($customer->company_id);
  }
});
