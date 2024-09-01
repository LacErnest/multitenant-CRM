<?php

use App\Models\LegalEntity;
use App\Services\CompanyLegalEntityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class CompanyLegalEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companyLegalService = App::make(CompanyLegalEntityService::class);

        $connection = Tenancy::getTenantConnectionName();
        $id =  DB::connection($connection)->getDatabaseName();
        $id = substr_replace($id, '-', 8, 0);
        $id = substr_replace($id, '-', 13, 0);
        $id = substr_replace($id, '-', 18, 0);
        $id = substr_replace($id, '-', 23, 0);

        $legalEntitiesIds = LegalEntity::get()->random(3)->pluck('id')->toArray();
        foreach ($legalEntitiesIds as $legalEntityId) {
            $companyLegalService->attach($id, $legalEntityId);
        }
    }
}
