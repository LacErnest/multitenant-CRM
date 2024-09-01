<?php

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Repositories\LegalEntityTemplateRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class LegalEntityTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntityTemplateRepository = App::make(LegalEntityTemplateRepository::class);

        $legalEntities = $legalEntityRepository->getAll();
        foreach ($legalEntities as $legalEntity) {
            $legalEntityTemplateRepository->addDefaultTemplates($legalEntity->id);
        }

    }
}
