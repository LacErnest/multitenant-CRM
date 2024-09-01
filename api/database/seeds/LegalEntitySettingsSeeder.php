<?php

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Services\LegalEntityTemplateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class LegalEntitySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalSettingRepository = App::make(LegalEntitySettingRepositoryInterface::class);
        $legalEntityTemplateService = App::make(LegalEntityTemplateService::class);

        $legalEntities = $legalEntityRepository->getAll();
        foreach ($legalEntities as $legalEntity) {
            $payload = [
                'quote_number'                   => 0,
                'quote_number_format'            => 'Q-[YYYY][MM]XXXX',
                'order_number'                   => 0,
                'order_number_format'            => 'O-[YYYY][MM]XXXX',
                'invoice_number'                 => 0,
                'invoice_number_format'          => 'I-[YYYY][MM]XXXX',
                'purchase_order_number'          => 0,
                'purchase_order_number_format'   => 'PO-[YYYY][MM]XXXX',
                'resource_invoice_number'        => 0,
                'resource_invoice_number_format' => 'RI-[YYYY][MM]XXXX',
                'invoice_payment_number'        => 0,
                'invoice_payment_number_format' => 'P-[YYYY][MM]XXXX',
            ];

            $setting = $legalSettingRepository->create($payload);
            $legalEntity->legal_entity_setting_id = $setting->id;
            $legalEntity->save();

            $legalEntityTemplateService->addDefaultTemplates($legalEntity->id);
        }
    }
}
