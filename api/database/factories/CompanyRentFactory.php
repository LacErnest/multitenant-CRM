<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\UserRole;
use App\Models\CompanyRent;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(CompanyRent::class, function (Faker $faker, $attributes) {
    $amount = ($attributes['end_date'] === null) ? $faker->numberBetween($min = 500, $max = 2000) : $faker->numberBetween($min = 100, $max = 500);

    return [
        'start_date'   => $attributes['start_date'],
        'name'         => $faker->word,
        'amount'       => $amount,
        'admin_amount' => convertLoanAmountToEuro(getTenantWithConnection(), $amount),
        'end_date'     => $attributes['end_date'],
        'author_id'    => User::where('role', UserRole::admin()->getIndex())->get()->random(1)->first()->id,
        'created_at'   => $attributes['start_date'],
    ];
});
