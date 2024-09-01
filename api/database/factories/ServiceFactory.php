<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(\App\Models\Service::class, function (Faker $faker) {
    $price = $faker->numberBetween($min = 35, $max = 25000);
    return [
        'name' => $faker->sentence($nbWords = 6, $variableNbWords = true),
        'price' => $price,
        'price_unit' => $price < 200 ? 'hours' : ($price > 2000 ? 'unit' : 'days')
    ];
});
