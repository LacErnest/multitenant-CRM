<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\App;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CompanySettingRepositoryInterface;

class InitCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $companyRepository = App::make(CompanyRepositoryInterface::class);
        $companySettingRepository = App::make(CompanySettingRepositoryInterface::class);
        $companies = $companyRepository->getAll();
        foreach ($companies as $company) {
            if(empty($company->setting)){
                $companySettingRepository->create(['company_id'=>$company->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
