<?php

use App\Enums\TablePreferenceType;
use App\Services\TablePreferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class UpdateTablePreferencesForExternalResources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addDetailsToExternalAccessPurchaseOrders();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->removeDetailsToExternalAccessPurchaseOrders();
    }

    private function addDetailsToExternalAccessPurchaseOrders()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->addDetailsToTablePreferences(TablePreferenceType::external_access_purchase_orders()->getIndex());
    }

    private function removeDetailsToExternalAccessPurchaseOrders()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->removeDetailsFromTablePreferences(TablePreferenceType::external_access_purchase_orders()->getIndex());
    }
}
