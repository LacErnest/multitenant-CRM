<?php

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CompanySettingRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companyRepository = App::make(CompanyRepositoryInterface::class);
        $companySettingRepository = App::make(CompanySettingRepositoryInterface::class);
        $companies = $companyRepository->getAll();
        foreach ($companies as $company) {
            $companySettingRepository->create(['company_id'=>$company->id]);
        }
    }
}
