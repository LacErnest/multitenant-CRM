<?php

use App\Models\Company;
use App\Models\GlobalTaxRate;
use App\Models\LegalEntity;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Company::all()->count() == 1) {
            $legalEntities = LegalEntity::all();

            foreach ($legalEntities as $entity) {
                GlobalTaxRate::create([
                    'legal_entity_id' => $entity->id,
                    'tax_rate' => 21,
                    'start_date' => '2012-01-01',
                ]);
            }
        }

        $company = Company::find(getTenantWithConnection());
        $legalEntityId = $company->defaultLegalEntity()->id;

        GlobalTaxRate::where('legal_entity_id', $legalEntityId)->delete();

        GlobalTaxRate::where('belonged_to_company_id', $company->id)
            ->update(['legal_entity_id' => $legalEntityId]);
    }
}
