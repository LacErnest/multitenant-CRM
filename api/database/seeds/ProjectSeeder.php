<?php

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::findOrFail(getTenantWithConnection());
        $totalMonths = ((date('Y', time()) - date('Y', strtotime($company->acquisition_date))) * 12) + (date('m', time()) - date('m', strtotime($company->acquisition_date)));
        $date = $company->acquisition_date;
        $customers = Customer::where('company_id', $company->id)->get();

        for ($x = 0; $x < $totalMonths; $x++) {
            $customer = $customers->random(1)->first();
            factory(App\Models\Project::class)
                ->create(['sales_person_id' => $customer->sales_person_id,
                    'contact_id' => $customer->primary_contact_id,
                    'created_at' => $date,
                    'budget' => 1]);

            $date =  Carbon::parse($date)->addMonth();
        }

        foreach ($customers as $customer) {
            $timestamp = mt_rand(strtotime($company->acquisition_date), time());
            factory(App\Models\Project::class, 2)
                ->create(['sales_person_id' => $customer->sales_person_id,
                    'contact_id' => $customer->primary_contact_id,
                    'created_at' =>  date("Y-m-d H:i:s", $timestamp),
                    'budget' => 0]);
        }

    }
}
