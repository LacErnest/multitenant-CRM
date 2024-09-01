<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\EmployeeHistory;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\CurrencyCode;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
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

$factory->define(App\Models\Resource::class, function (Faker $faker) {

    $statuses = ResourceStatus::getIndices();
    $types    = ResourceType::getIndices();

    static $statusCount = 0;
    static $typeCount = 0;

  if ($statusCount > count($statuses) - 1) {
      $statusCount = 0;
  }
  if ($typeCount > count($types) - 1) {
      $typeCount = 0;
  }

    $email = $faker->unique()->email;

  if ($user = Resource::where('email', $email)->first()) {
      $email = Str::random(5).$email;
  }
    $hourRate = $faker->randomFloat(2, 10, 90);

    $company = Company::find(getTenantWithConnection());
    $legalEntityId = $company->legalEntities()->wherePivot('default', true)->first()->id;

    return [
        'id' => Uuid::uuid(),
        'xero_id' => Uuid::uuid(),
        'status' => $statuses[$statusCount++],
        'type' => $types[$typeCount++],
        'name' => $faker->name,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $email,
        'tax_number' => $faker->randomNumber(),
        'default_currency' => rand(0, 1) ? CurrencyCode::USD()->getIndex() : CurrencyCode::EUR()->getIndex(),
        'hourly_rate' => $hourRate,
        'daily_rate' => $hourRate * 9,
        'phone_number' => $faker->phoneNumber,
        'job_title' => $faker->word,
        'address_id' => function () {
            return factory(App\Models\Address::class)->create()->id;
        },
        'legal_entity_id' => $legalEntityId,
    ];
});
