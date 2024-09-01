<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\Country;
use Faker\Generator as Faker;
use \Faker\Provider\Uuid;
use Faker\Provider\cs_CZ\Address;
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

$factory->define(App\Models\CustomerAddress::class, function (Faker $faker) {

    return [
        'id' => Uuid::uuid(),
        'addressline_1' => $faker->address,
        'addressline_2' => $faker->address,
        'city' => $faker->city,
        'region' => Address::region(),
        'postal_code' => $faker->postcode,
        'country' => mt_rand(0, 100) > 50 ? array_rand(Country::getIndices()) : Country::Ireland()->getIndex()
    ];
});
