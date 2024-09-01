<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Models\Contact;
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

$factory->define(Contact::class, function (Faker $faker, $attributes) {

    $email = $faker->unique()->email;

  if ($user = Contact::where('email', $email)->first()) {
      $email = Str::random(5).$email;
  }

    return [
        'id' => Uuid::uuid(),
        'customer_id' => $attributes['customer_id'],
        'first_name'  => $faker->firstName,
        'last_name'   => $faker->lastName,
        'email'       => $email,
        'phone_number' => $faker->phoneNumber,
        'title'       => $faker->word,
        'department'  => $faker->text('30'),
    ];
});
