<?php


namespace App\Services;

use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\Project\AssignEmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectEmployee;
use App\Repositories\EmployeeRepository;
use App\Repositories\ServiceRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Log;

class ProjectEmployeeService
{
    protected EmployeeRepository $employee_repository;

    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepository $employee_repository, EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employee_repository = $employee_repository;
        $this->employeeRepository = $employeeRepository;
    }

    public function get($employee_id)
    {
        return $this->employee_repository->get($employee_id);
    }

    public function create(string $company_id, string $project_id, string $employee_id, AssignEmployeeRequest $request): bool
    {
        $project = Project::findOrFail($project_id);

        $employee = Employee::find($employee_id);
        if (!$employee) {
            $employeeService = App::make(EmployeeService::class);
            $employee = $employeeService->findBorrowedEmployee($employee_id);
            if (!$employee || !$employee->can_be_borrowed) {
                throw new UnprocessableEntityHttpException('This employee can not be assigned.');
            }
        }

        if ($employee) {
            if (!$employee->salary || $employee->salary == 0) {
                throw new UnprocessableEntityHttpException('Employee has no salary set.');
            }
            if (!$employee->working_hours || $employee->working_hours == 0) {
                throw new UnprocessableEntityHttpException('Employee has no working hours set.');
            }
            $month = date('Y-m', strtotime($request->input('year') . '-' . $request->input('month'))) . '-01';
            $rates = getCurrencyRates();
            $rateEurToUsd = getOrderEurToUsdRate($company_id, $project->order->id);
            $employeeRate = $rates['rates'][CurrencyCode::make((int)$employee->default_currency)->__toString()];
            $projectEmployee = ProjectEmployee::where([['project_id', $project_id], ['employee_id', $employee->id], ['month', $month]])->first();

            $workingHoursRate = getWorkingHoursRate($employee, $month);
            if (!$projectEmployee) {
                $employeeCost = $workingHoursRate * $request->input('hours');
                $project->employees()->attach($employee_id, [
                'hours' => $request->input('hours'),
                'employee_cost' => $employeeCost,
                'currency_rate_dollar' => $rateEurToUsd,
                'currency_rate_employee' => $employeeRate,
                'month' => $month,
                ]);
                calculateEmployeeCosts($company_id, $employee, $project, $request->input('hours'), $rateEurToUsd, $employeeRate, $month);
            } else {
                $workingHoursRate = getWorkingHoursRate($employee, $month);
                $extraHours = $request->input('hours') + $projectEmployee->hours;
                $extraEmployeeCost = $workingHoursRate * $extraHours;
                $project->employees()->wherePivot('month', $month)->updateExistingPivot($employee_id, [
                'hours' => $extraHours,
                'employee_cost' => $extraEmployeeCost,
                'month' => $month,
                ]);
                calculateEmployeeCosts($company_id, $employee, $project, $request->input('hours'), $projectEmployee->currency_rate_dollar, $projectEmployee->currency_rate_employee, $month);
            }
            return true;
        }
        return false;
    }

    public function update(string $company_id, string $project_id, string $employee_id, AssignEmployeeRequest $request): bool
    {
        $project = Project::findOrFail($project_id);
        $month = date('Y-m', strtotime($request->input('year') . '-' . $request->input('month'))) . '-01';
        $monthToSearch = $month;
        $projectResource = $project->employees()->where('employee_id', $employee_id)->wherePivot('month', $month)->first();

        if (!$projectResource) {
            $employeeService = App::make(EmployeeService::class);
            $projectResource = $employeeService->findBorrowedEmployee($employee_id);
        }

        if ($projectResource) {
            $employee = ProjectEmployee::where([['project_id', $project_id], ['employee_id', $employee_id], ['month', $month]])->first();
            if (!$employee) {
                $employee = ProjectEmployee::where([['project_id', $project_id], ['employee_id', $employee_id]])->first();
                $monthToSearch = null;
            }
            if (!$employee) {
                return response()->json(['message' => 'Could not find employee on project.']);
            }

            $hoursMargin = $request->input('hours') - $employee->hours;
            $projectResource->hours = $employee->hours;
            $workingHoursRate = getWorkingHoursRate($employee->employee, $month);
            $extraHours = $request->input('hours');

            if ($request->input('hours') == 0) {
                $project->employees()->wherePivot('month', $monthToSearch)->detach($employee_id);
            } else {
                $extraEmployeeCost = $workingHoursRate * $extraHours;
                $project->employees()->wherePivot('month', $monthToSearch)->updateExistingPivot($employee_id, [
                'hours' => $request->input('hours'),
                'employee_cost' => $extraEmployeeCost,
                'month' => $month,
                ]);
            }

            calculateEmployeeCosts(
                $company_id,
                $projectResource,
                $project,
                $hoursMargin,
                $employee->currency_rate_dollar,
                $employee->currency_rate_employee,
                $month
            );
            return true;
        }
        return false;
    }

    public function refresh(Company $company, Project $project, $catchException = true): void
    {
        $project->employee_costs = 0;
        $project->employee_costs_usd = 0;
        $project->external_employee_costs = 0;
        $project->external_employee_costs_usd = 0;
        foreach ($project->employees as $employee) {
            try {
                if (!empty($employee->pivot->month)) {
                    $month = $employee->pivot->month;
                    $project_employee = ProjectEmployee::where([['project_id', $project->id], ['employee_id', $employee->id], ['month', $month]])->first();
                    $workingHoursRate = getWorkingHoursRate($employee, $month);
                    $extraHours = $project_employee->hours;

                    $extraEmployeeCost = $workingHoursRate * $extraHours;
                    $project->employees()->wherePivot('month', $month)->updateExistingPivot($employee->id, [
                    'employee_cost' => $extraEmployeeCost,
                    ]);
                    $currencyRateEmployee = safeDivide(1, $project_employee->currency_rate_employee);
                    $project->employee_costs += $extraEmployeeCost * $currencyRateEmployee;
                    $project->employee_costs_usd += $extraEmployeeCost * $currencyRateEmployee * $project_employee->currency_rate_dollar;
                    if ($employee->can_be_borrowed && isset($employee->company_id) && $employee->company_id != getTenantWithConnection()) {
                            $project->external_employee_costs += $extraEmployeeCost * $currencyRateEmployee;
                            $project->external_employee_costs_usd += $extraEmployeeCost * $currencyRateEmployee * $project_employee->currency_rate_dollar;
                    }
                }
            } catch (\Throwable $th) {
                Log::error($th);
                if (!$catchException) {
                    throw $th;
                }
            }
        }
        $project->save();
    }
}
