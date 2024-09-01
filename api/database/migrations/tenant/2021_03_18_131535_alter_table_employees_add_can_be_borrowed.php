<?php

use App\Enums\TablePreferenceType;
use App\Services\TablePreferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class AlterTableEmployeesAddCanBeBorrowed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('can_be_borrowed')->default(false)->after('phone_number');
        });

        Schema::table('project_employees', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('can_be_borrowed');
        });

        Schema::table('project_employees', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees');
        });

        $this->removeIsBorrowedFromTablePreferences();
    }

    private function addIsBorrowedToTablePreferences()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->addIsBorrowedToTablePreferences(TablePreferenceType::external_access_purchase_orders()->getIndex());
    }

    private function removeIsBorrowedFromTablePreferences()
    {
        $tablePreferenceService = App::make(TablePreferenceService::class);

        $tablePreferenceService->removeIsBorrowedFromTablePreferences(TablePreferenceType::external_access_purchase_orders()->getIndex());
    }
}
