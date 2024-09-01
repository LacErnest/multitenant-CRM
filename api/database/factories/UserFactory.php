<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\CommissionModel;
use App\Models\SalesCommission;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use \Faker\Provider\Uuid;
use App\Enums\UserRole;
use App\Models\User;
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

$factory->define(User::class, function (Faker $faker) {

    $roles = UserRole::getIndices();
    static $count = 0;

  if ($count > count($roles) - 1) {
      $count = 0;
  }

  if (UserRole::isAdmin($roles[$count])) {
      $count++;
  }

    $email = $faker->unique()->email;

  if ($user = User::where('email', $email)->first()) {
      $email = Str::random(5).$email;
  }

    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $email,
        'password' => 'password', // password
        'remember_token' => Str::random(10),
        'role' => $roles[$count++],
        'google2fa' => false,
    ];
});

$factory->afterCreating(App\Models\User::class, function ($user) {
  if (UserRole::isSales($user->role)) {
      $salesType = array_rand(CommissionModel::getIndices());
    switch ($salesType) {
      case CommissionModel::default()->getIndex():
        $commission = SalesCommission::DEFAULT_COMMISSION;
        $secondSale = 0;
          break;
      case CommissionModel::lead_generation()->getIndex():
          $commission = SalesCommission::DEFAULT_LEAD_COMMISSION;
          $secondSale = SalesCommission::DEFAULT_SECOND_SALE;
          break;
      case CommissionModel::lead_generationB()->getIndex():
          $commission = SalesCommission::DEFAULT_LEAD_COMMISSION_B;
          $secondSale = 0;
          break;
      case CommissionModel::sales_support()->getIndex():
          $commission = 1;
          $secondSale = 0;
          break;
      case CommissionModel::custom_modelA()->getIndex():
            $commission = 5;
            $secondSale = 0;
            break;
      default:
          $commission = 0;
          $secondSale = 0;
          break;
    }

      SalesCommission::create([
          'commission' => $commission,
          'second_sale_commission' => $secondSale,
          'sales_person_id' => $user->id,
          'commission_model' => $salesType,
      ]);
  }
});
