<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\EmployeeType;
use Faker\Generator as Faker;
use App\Enums\ResourceType;
use Carbon\Carbon;

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

$factory->define(App\Models\ProjectEmployee::class, function (Faker $faker) {
    return [
        'hours' => $faker->numberBetween(1, 100),
        'month'=> Carbon::now()->firstOfMonth()->format('Y-m-d'),
        'resource_id' => function () {
            return factory(App\Models\Employee::class)->create(['type' => EmployeeType::Employee()->getIndex()])->id;
        }
    ];
});
