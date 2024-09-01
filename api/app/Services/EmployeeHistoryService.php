<?php

namespace App\Services;

use App\Contracts\Repositories\EmployeeHistoryRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EmployeeHistoryService
{
    protected EmployeeHistoryRepositoryInterface $employeeHistoryRepository;

    public function __construct(EmployeeHistoryRepositoryInterface $employeeHistoryRepository)
    {
        $this->employeeHistoryRepository = $employeeHistoryRepository;
    }

    public function create(string $companyId, string $employeeId, array $attributes)
    {
        $employee = Employee::findOrFail($employeeId);
        $currency = Company::where('id', $companyId)->first()->currency_code;
        $rates = getCurrencyRates();
        $rate = $rates['rates']['USD'];
        $employeeCurrency = CurrencyCode::make((int)$employee->default_currency)->__toString();
        $employeeRate = $rates['rates'][$employeeCurrency];
        $employeeHistory = [
          'employee_id' => $employee->id,
          'working_hours' => $attributes['working_hours'],
          'salary' => ceiling($attributes['employee_salary'] * safeDivide(1, $employeeRate), 2),
          'salary_usd' => ceiling($attributes['employee_salary'] * safeDivide(1, $employeeRate) * $rate, 2),
          'start_date' => $attributes['start_date'],
          'end_date' => $attributes['end_date'],
          'currency_rate' => $currency == CurrencyCode::EUR()->getIndex() ? $rate : safeDivide(1, $rate),
          'employee_salary' => $attributes['employee_salary'],
          'default_currency' => $employee->default_currency,
          'currency_rate_employee' => $employeeRate,
        ];

        $this->closeEmployeeHistoryEndDate($employee, $attributes['start_date']);

        $this->employeeHistoryRepository->create($employeeHistory);
    }

    public function update(string $companyId, string $employeeId, string $historyId, array $attributes)
    {
        $employeeHistory = $this->employeeHistoryRepository->firstById($historyId);

        if ($attributes['employee_salary'] != $employeeHistory->employee_salary) {
            $currency = Company::where('id', $companyId)->first()->currency_code;
            $attributes['salary'] = ceiling($attributes['employee_salary'] * safeDivide(1, $employeeHistory->currency_rate_employee), 2);

            if ($currency == CurrencyCode::EUR()->getIndex()) {
                $attributes['salary_usd'] = ceiling($attributes['salary'] * ($employeeHistory->currency_rate), 2);
            } else {
                $attributes['salary_usd'] = ceiling($attributes['salary'] * safeDivide(1, $employeeHistory->currency_rate), 2);
            }
        }
        $this->employeeHistoryRepository->update($historyId, $attributes);
    }

    public function delete(string $historyId)
    {
        $employeeHistory = $this->employeeHistoryRepository->firstById($historyId);
        $this->employeeHistoryRepository->delete($historyId);
    }

    private function closeEmployeeHistoryEndDate(Employee $employee, $startDate)
    {
        $histories = $employee->histories()
          ->whereNull('end_date')
          ->where('start_date', '<=', $startDate)->get();
        foreach ($histories as $history) {
            $history->update([
            'end_date' => Carbon::parse($startDate)->subDay()->format('Y-m-d')
            ]);
        }
    }
}
