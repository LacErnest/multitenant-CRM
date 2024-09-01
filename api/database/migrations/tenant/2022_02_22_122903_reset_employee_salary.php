<?php

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\EmployeeHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResetEmployeeSalary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->setEmployeeSalary();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    private function setEmployeeSalary()
    {
        $histories = EmployeeHistory::all();
        foreach ($histories as $history) {
            if ($history->default_currency == CurrencyCode::EUR()->getIndex()) {
                $history->salary = $history->employee_salary;
            } elseif ($history->default_currency == CurrencyCode::USD()->getIndex()) {
                $history->salary_usd = $history->employee_salary;
            } else {
                continue;
            }
            $history->save();
        }
    }
}
