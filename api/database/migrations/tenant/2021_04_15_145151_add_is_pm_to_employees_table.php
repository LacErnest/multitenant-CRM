<?php

use App\Enums\EmployeeType;
use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPmToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_pm')->default(0)->after('status');
        });

        $this->setPmAsEmployee();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->reversePmAsEmployee();

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('is_pm');
        });
    }

    private function setPmAsEmployee()
    {
        $employees = Employee::all();
        foreach ($employees as $employee) {
            if ($employee->type == 1) {
                $employee->is_pm = true;
                $employee->type = EmployeeType::employee()->getIndex();
                $employee->save();
            }
        }
    }

    private function reversePmAsEmployee()
    {
        $employees = Employee::all();
        foreach ($employees as $employee) {
            if ($employee->is_pm = true) {
                $employee->type = 1;
                $employee->save();
            }
        }
    }
}
