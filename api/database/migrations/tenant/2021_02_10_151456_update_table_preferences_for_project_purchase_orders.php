<?php

use App\Services\TablePreferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class UpdateTablePreferencesForProjectPurchaseOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addDetailsToProjectPurchaseOrders();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->removeDetailsFromProjectPurchaseOrders();
    }

    private function addDetailsToProjectPurchaseOrders()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->addDetailsToTablePreferences();
    }

    private function removeDetailsFromProjectPurchaseOrders()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->removeDetailsFromTablePreferences();
    }
}
