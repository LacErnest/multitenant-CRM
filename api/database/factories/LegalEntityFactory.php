<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Bank;
use App\Models\CustomerAddress;
use App\Models\LegalEntity;
use Faker\Generator as Faker;

$factory->define(LegalEntity::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'vat_number' => $faker->countryCode . $faker->randomNumber($nbDigits = 8),
        'address_id' => function () {
            return factory(CustomerAddress::class)->create()->id;
        },
        'european_bank_id' => function () {
            return factory(Bank::class)->create()->id;
        },
        'american_bank_id' => function () {
            return factory(Bank::class)->create()->id;
        },
        'usdc_wallet_address' => $faker->address
    ];
});
