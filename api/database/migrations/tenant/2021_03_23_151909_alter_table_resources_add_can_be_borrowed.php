<?php

use App\Enums\TablePreferenceType;
use App\Services\TablePreferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class AlterTableResourcesAddCanBeBorrowed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->boolean('can_be_borrowed')->default(false)->after('phone_number');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['resource_id']);
        });

        Schema::table('resource_logins', function (Blueprint $table) {
            $table->dropForeign(['resource_id']);
        });

        $this->addIsBorrowedToTablePreferences();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('can_be_borrowed');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('resource_id')->references('id')->on('resources');
        });

        Schema::table('resource_logins', function (Blueprint $table) {
            $table->foreign('resource_id')->references('id')->on('resources');
        });

        $this->removeIsBorrowedFromTablePreferences();
    }

    private function addIsBorrowedToTablePreferences()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->addIsBorrowedToTablePreferences(TablePreferenceType::project_employees()->getIndex());
        $tablePreferenceService->addDetailsToTablePreferences(TablePreferenceType::external_access_purchase_orders()->getIndex());
    }

    private function removeIsBorrowedFromTablePreferences()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->removeIsBorrowedFromTablePreferences(TablePreferenceType::project_employees()->getIndex());
    }
}
