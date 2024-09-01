<?php

use App\Services\EmployeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class AddDefaultCurrencyToEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('default_currency')->nullable()->after('working_hours');
        });

        $this->setCompanyCurrencyAsDefaultCurrency();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('default_currency');
        });
    }

    private function setCompanyCurrencyAsDefaultCurrency(): void
    {
        $employeeService = App::make(EmployeeService::class);

        $employeeService->setCompanyCurrencyAsDefaultCurrency();
    }
}
