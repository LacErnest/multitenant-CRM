<?php

use App\Enums\CurrencyCode;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectEmployee;
use App\Services\EmployeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AddExternalEmployeeCostsToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('external_employee_costs', 15, 6)->default(0)->after('employee_costs_usd');
            $table->decimal('external_employee_costs_usd', 15 , 6)->default(0)->after('external_employee_costs');
        });

        $this->saveExternalEmployeeCosts();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['external_employee_costs', 'external_employee_costs_usd']);
        });
    }

    private function saveExternalEmployeeCosts()
    {
        $employeeService = App::make(EmployeeService::class);
        $rates = getCurrencyRates();

        $projectEmployees = ProjectEmployee::all();
        $employeeIds = Employee::all()->pluck('id')->toArray();
        foreach ($projectEmployees as $projectEmployee) {
            if (!in_array($projectEmployee->employee_id, $employeeIds)) {
                $employee = $employeeService->findBorrowedEmployee($projectEmployee->employee_id);
                if ($employee) {
                    $employeeCurrency = CurrencyCode::make((int)$employee->default_currency)->__toString();
                    $employeeRate = $rates['rates'][$employeeCurrency];
                    $project = Project::find($projectEmployee->project_id);
                    if ($project) {
                        $project->external_employee_costs += safeDivide($employee->salary , $employee->working_hours) * $projectEmployee->hours * safeDivide(1, $employeeRate);
                        $project->external_employee_costs_usd += safeDivide($employee->salary , $employee->working_hours) * $projectEmployee->hours * safeDivide(1, $employeeRate) * $rates['rates']['USD'];
                        $project->save();
                    }
                }
            }
        }
    }
}
