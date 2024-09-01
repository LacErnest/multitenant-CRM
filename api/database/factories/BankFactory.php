<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Bank;
use App\Models\CustomerAddress;
use Faker\Generator as Faker;

$factory->define(Bank::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'iban' => $faker->iban(),
        'bic' => $faker->swiftBicNumber,
        'account_number' => $faker->bankAccountNumber,
        'routing_number' => $faker->bankRoutingNumber,
        'usa_account_number' => $faker->bankAccountNumber,
        'usa_routing_number' => $faker->bankRoutingNumber,
        'address_id' => function () {
            return factory(CustomerAddress::class)->create()->id;
        },
    ];
});
