<?php

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\CompanyLoan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class CompanyLoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(CompanyLoan::class, 5)->create();
    }
}
