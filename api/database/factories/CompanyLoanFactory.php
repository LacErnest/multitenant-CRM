<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\UserRole;
use App\Models\CompanyLoan;
use App\Models\LoanPaymentLog;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(CompanyLoan::class, function (Faker $faker, $rate) {

    $issueDate = $faker->dateTimeBetween($startDate = '-2 years', $endDate = 'now', $timezone = null);
    $amount = $faker->numberBetween($min = 50000, $max = 200000);
    $payDate = rand(0, 1) ?
        $faker->dateTimeInInterval($startDate = $issueDate, $interval = '+ 8 months', $timezone = null) : null;
  if ($payDate) {
      $amountLeft = 0;
      $adminLeft= 0;
  } else {
      $amountLeft = $amount;
      $adminLeft = convertLoanAmountToEuro(getTenantWithConnection(), $amount);
  }

    return [
        'issued_at'         => $issueDate,
        'amount'            => $amount,
        'admin_amount'      => convertLoanAmountToEuro(getTenantWithConnection(), $amount),
        'amount_left'       => $amountLeft,
        'admin_amount_left' => $adminLeft,
        'paid_at'           => $payDate,
        'author_id'         => User::where('role', UserRole::admin()->getIndex())->get()->random(1)->first()->id,
        'created_at'        => $issueDate,
    ];
});

$factory->afterCreating(CompanyLoan::class, function ($loan) {
  if ($loan->paid_at !== null) {
      LoanPaymentLog::create([
          'loan_id'       => $loan->id,
          'amount'        => $loan->amount,
          'admin_amount'  => $loan->admin_amount,
          'pay_date'      => $loan->paid_at,
      ]);
  }
});
