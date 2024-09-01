<?php

use App\Models\Company;
use App\Models\CompanyRent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompanyRentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::find(getTenantWithConnection());
        $totalMonths = ((date('Y', time()) - date('Y', strtotime($company->acquisition_date))) * 12)
            + (date('m', time()) - date('m', strtotime($company->acquisition_date)));
        $totalRent = 0;
        $date = $company->acquisition_date;

        while ($totalRent < 10000) {
            $rent = factory(CompanyRent::class)->create([
                'start_date' => $company->acquisition_date,
                'end_date' => null,
            ]);
            $totalRent += $rent->admin_amount;
        }

        for ($x = 0; $x < $totalMonths; $x++) {
            if (mt_rand(0,100) < 30) {
                $firstOfMonth = date('Y-m-01', strtotime($date));
                $firstOfNextMonth = date('Y-m-d', strtotime('+1 month', strtotime($firstOfMonth)));
                factory(CompanyRent::class)->create([
                    'start_date' => $firstOfMonth,
                    'end_date' => $firstOfNextMonth,
                ]);
            }

            $date =  Carbon::parse($date)->addMonth();
        }

    }
}
