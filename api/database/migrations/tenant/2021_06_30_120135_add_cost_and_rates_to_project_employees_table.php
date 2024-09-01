<?php

use App\Enums\CurrencyCode;
use App\Enums\TablePreferenceType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ProjectEmployee;
use App\Models\TablePreference;
use App\Services\EmployeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AddCostAndRatesToProjectEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_employees', function (Blueprint $table) {
            $table->decimal('employee_cost')->default(0)->after('hours');
            $table->decimal('currency_rate_dollar', 13, 6)->nullable();
            $table->decimal('currency_rate_employee', 13, 6)->nullable();
        });

        TablePreference::where('type', TablePreferenceType::project_employees()->getIndex())
            ->update(['columns' => '["first_name", "last_name", "type", "status", "email", "phone_number",
            "hours", "employee_cost", "is_borrowed"]']);

        $this->addRatesToExistingRecords();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TablePreference::where('type', TablePreferenceType::project_employees()->getIndex())
            ->update(['columns' => '["first_name", "last_name", "type", "status", "email", "phone_number", "hours", "is_borrowed"]']);

        Schema::table('project_employees', function (Blueprint $table) {
            $table->dropColumn('employee_cost', 'currency_rate_dollar', 'currency_rate_employee');
        });
    }

    private function addRatesToExistingRecords()
    {
        $rates = getCurrencyRates();
        $rateEuroToDollar = $rates['rates']['USD'];
        $employees = Employee::all();
        foreach ($employees as $employee) {
            $hourlyRate = 0;
            $projects = $employee->projects()->get();
            if ($projects->isNotEmpty()) {
                if ($employee->salary && $employee->working_hours) {
                    $hourlyRate = safeDivide($employee->salary , $employee->working_hours);
                }
                if ($employee->default_currency) {
                    $employeeRate = $rates['rates'][CurrencyCode::make((int)$employee->default_currency)->__toString()];
                } else {
                    $company = Company::find(getTenantWithConnection());
                    $employeeRate = $rates['rates'][CurrencyCode::make((int)$company->currency_code)->__toString()];
                }
                foreach ($projects as $project) {
                    $employee->projects()->updateExistingPivot($project->id, [
                        'employee_cost' => $project->pivot->hours * $hourlyRate,
                        'currency_rate_dollar' => $rateEuroToDollar,
                        'currency_rate_employee' => $employeeRate,
                    ]);
                }
            }
        }

        $externalEmployees = ProjectEmployee::where('employee_cost', 0)->get();
        if ($externalEmployees->isNotEmpty()) {
            $employeeService = App::make(EmployeeService::class);
            foreach ($externalEmployees as $externalEmployee) {
                $employee = $employeeService->findBorrowedEmployee($externalEmployee->employee_id);
                $hourlyRate = 0;
                if ($employee) {
                    if ($employee->salary && $employee->working_hours) {
                        $hourlyRate = safeDivide($employee->salary , $employee->working_hours);
                    }
                    if ($employee->default_currency) {
                        $employeeRate = $rates['rates'][CurrencyCode::make((int)$employee->default_currency)->__toString()];
                    } else {
                        $employeeCompany = Company::find($employee->company_id);
                        $employeeRate = $rates['rates'][CurrencyCode::make((int)$employeeCompany->currency_code)->__toString()];
                    }
                    ProjectEmployee::where([['employee_id', $externalEmployee->employee_id], ['project_id', $externalEmployee->project_id]])
                        ->update([
                           'employee_cost' => $hourlyRate * $externalEmployee->hours,
                           'currency_rate_dollar' => $rateEuroToDollar,
                           'currency_rate_employee' => $employeeRate,
                        ]);
                }
            }
        }
    }
}
