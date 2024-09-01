<?php

use App\Models\EmployeeHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkingHoursToEmployeeHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_histories', function (Blueprint $table) {
            $table->integer('working_hours')->nullable()->after('employee_salary');
        });

        foreach (EmployeeHistory::all() as $employeeHistory) {
            $employeeHistory->working_hours = $employeeHistory->employee->working_hours;
            $employeeHistory->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_histories', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });
    }
}
