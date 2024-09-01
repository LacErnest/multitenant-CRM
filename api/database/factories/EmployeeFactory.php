<?php

/** @var Factory $factory */

use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\CurrencyCode;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Cache;
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

$factory->define(App\Models\Employee::class, function (Faker $faker, $attributes) {

    $status = $attributes['status'] ?? mt_rand(0, 100) < 70 ?  EmployeeStatus::active()->getIndex() : array_rand(EmployeeStatus::getIndices());
    $email = $faker->unique()->email;

  if ($user = Employee::where('email', $email)->first()) {
      $email = Str::random(5).$email;
  }

  if ($attributes['type'] == EmployeeType::employee()->getIndex() && array_key_exists('is_pm', $attributes)) {
      $isPm = 1;
      $status = EmployeeStatus::active()->getIndex();
  } else {
      $isPm = mt_rand(0, 100) < 30;
  }

    $partTime = mt_rand(0, 100) < 80;
    $working_hours =  $partTime ? 80 : 160;
    $salary = $partTime ? $faker->numberBetween($min = 1250, $max = 3000) : $faker->numberBetween($min = 3000, $max = 6000);
    $company = Company::find(getTenantWithConnection());
    $legalEntityId = $company->legalEntities()->wherePivot('default', true)->first()->id;

    return [
        'xero_id' => Uuid::uuid(),
        'status' => $status,
        'type' => $attributes['type'],
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $email,
        'salary' => $salary,
        'working_hours' => $working_hours,
        'phone_number' => $faker->phoneNumber,
        'address_id' => function () {
            return factory(App\Models\Address::class)->create()->id;
        },
        'default_currency' => $company->currency_code == CurrencyCode::EUR()->getIndex() ? CurrencyCode::EUR()->getIndex() : CurrencyCode::USD()->getIndex(),
        'legal_entity_id' => $legalEntityId,
        'is_pm' => $isPm,
    ];
});

$factory->afterCreating(Employee::class, function ($employee, $faker) {
   $company = Company::find(getTenantWithConnection());
   $date = mt_rand(0, 100) < 70 ? $company->acquisition_date : $faker->dateTimeBetween($startDate = $company->acquisition_date, $endDate = 'now', $timezone = null);
   $rates = getCurrencyRates();
   $rate = $rates['rates']['USD'];
   $employeeCurrency = CurrencyCode::make((int)$employee->default_currency)->__toString();
   $employeeRate = $rates['rates'][$employeeCurrency];

   EmployeeHistory::create([
       'employee_id' => $employee->id,
       'salary' => ceiling($employee->salary * (1/$employeeRate), 2),
       'working_hours' => $employee->working_hours,
       'salary_usd' => ceiling($employee->salary * (1/$employeeRate) * $rate, 2),
       'start_date' => $date,
       'end_date' => EmployeeStatus::isActive($employee->status) ? null : $faker->dateTimeBetween($startDate = $date, $endDate = 'now', $timezone = null),
       'currency_rate' => $company->currency_code == CurrencyCode::EUR()->getIndex() ? $rate : 1/$rate,
       'employee_salary' => $employee->salary,
       'default_currency' => $employee->default_currency,
       'currency_rate_employee' => $employeeRate,
   ]);
});
