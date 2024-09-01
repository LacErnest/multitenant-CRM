<?php

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyEmployeeAndRateToEmployeeHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_histories', function (Blueprint $table) {
            $table->decimal('employee_salary', 9, 2)->nullable()->after('salary_usd');
            $table->decimal('currency_rate_employee', 13, 6)->nullable()->after('currency_rate');
            $table->unsignedTinyInteger('default_currency')->nullable()->after('end_date');
        });

        $this->setCurrencyValues();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_histories', function (Blueprint $table) {
            $table->dropColumn('employee_salary', 'currency_rate_employee', 'default_currency');
        });
    }

    private function setCurrencyValues()
    {
        $companyCurrency = Company::where('id', getTenantWithConnection())->first()->currency_code;
        $histories = EmployeeHistory::all();
        foreach ($histories as $history) {
            $employee = Employee::find($history->employee_id);
            if ($employee) {
                $history->default_currency = $employee->default_currency;
                $history->employee_salary = $employee->salary;
                if ($employee->default_currency == $companyCurrency) {
                    $history->currency_rate_employee = 1;
                } else {
                    $history->currency_rate_employee = safeDivide(1 , $history->currency_rate);
                }
                $history->save();
            }
        }

    }
}
