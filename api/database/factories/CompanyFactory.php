<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\CurrencyCode;
use Faker\Generator as Faker;
use \Faker\Provider\Uuid;
use App\Enums\UserRole;
/*    dd($types);
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/


$factory->define(App\Models\Company::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'currency_code' => rand(0, 1) ? CurrencyCode::EUR()->getIndex() : CurrencyCode::USD()->getIndex(),
        'acquisition_date' => $faker->dateTimeBetween($startDate = '-2 years', $endDate = 'now', $timezone = null)        ,
        'earnout_years' => $faker->numberBetween($min = 3, $max = 6),
        'earnout_bonus' => $faker->numberBetween($min = 5, $max = 15),
        'gm_bonus' => $faker->numberBetween($min = 5, $max = 20),
    ];
});
