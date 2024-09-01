<?php

namespace App\Observers;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Events\EmployeeSalariesUpdatedEvent;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Support\Facades\Cache;

class EmployeeObserver
{
    public function created(Employee $employee)
    {
        if (EmployeeStatus::isActive($employee->status) && !($employee->salary === null)) {
            $this->createHistory($employee);
        }
    }
    public function updated(Employee $employee)
    {
        if ($employee->is_pm == true && ($employee->isDirty('first_name') || $employee->isDirty('last_name'))) {
            $id = getTenantWithConnection();
            ElasticUpdateAssignment::dispatch($id, Employee::class, $employee->id)->onQueue('low');
        }

        if ($employee->wasChanged('status')) {
            if (EmployeeStatus::isActive($employee->status) && !($employee->salary === null)) {
                $this->createHistory($employee);
            } else {
                if (EmployeeStatus::isActive($employee->getOriginal('status'))) {
                    $history = EmployeeHistory::where([['employee_id', $employee->id], ['end_date', null]])->first();
                    if ($history) {
                        $history->end_date = now();
                        $history->save();
                    }
                }
            }
        }

        if (($employee->wasChanged('salary') || $employee->wasChanged('working_hours') || $employee->wasChanged('default_currency')) && EmployeeStatus::isActive($employee->status)) {
            if (
            $employee->salary != $employee->getOriginal('salary') ||
            $employee->working_hours != $employee->getOriginal('working_hours') ||
            $employee->default_currency != $employee->getOriginal('default_currency')
            ) {
                $history = EmployeeHistory::where([['employee_id', $employee->id], ['end_date', null]])->first();
                if ($history) {
                    $history->end_date = now();
                    $history->save();
                }

                $this->createHistory($employee);
            }
        }

        if ($employee->wasChanged('working_hours') && EmployeeStatus::isActive($employee->status)) {
            if ($employee->working_hours != $employee->getOriginal('working_hours')) {
                event(new EmployeeSalariesUpdatedEvent($employee));
            }
        }
    }

    private function createHistory($employee)
    {
        if (!config('settings.seeding')) {
            $currency = Company::where('id', getTenantWithConnection())->first()->currency_code;
            $rates = getCurrencyRates();
            $rate = $rates['rates']['USD'];
            $employeeCurrency = CurrencyCode::make((int)$employee->default_currency)->__toString();
            $employeeRate = $rates['rates'][$employeeCurrency];

            EmployeeHistory::create([
            'employee_id' => $employee->id,
            'working_hours' => $employee->working_hours,
            'salary' => ceiling($employee->salary * safeDivide(1, $employeeRate), 2),
            'salary_usd' => ceiling($employee->salary * safeDivide(1, $employeeRate) * $rate, 2),
            'start_date' => now(),
            'currency_rate' => $currency == CurrencyCode::EUR()->getIndex() ? $rate : safeDivide(1, $rate),
            'employee_salary' => $employee->salary,
            'default_currency' => $employee->default_currency,
            'currency_rate_employee' => $employeeRate,
            ]);
        }
    }
}
