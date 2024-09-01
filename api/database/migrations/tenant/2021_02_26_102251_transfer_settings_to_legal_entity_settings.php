<?php

use App\Services\LegalEntitySettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class TransferSettingsToLegalEntitySettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->migrateFormerSettingsData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->rollbackFormerSettingsData();
    }

    public function migrateFormerSettingsData()
    {
        $legalEntitySettingsService = App::make(LegalEntitySettingsService::class);

        $legalEntitySettingsService->migrateFormerSettingsToLegalEntitySettings();
    }

    public function rollbackFormerSettingsData()
    {
        $legalEntitySettingsService = App::make(LegalEntitySettingsService::class);

        $legalEntitySettingsService->rollbackLegalEntitySettingsToFormer();
    }
}
