<?php

namespace App\Console\Commands\Scheduler;

use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\EmployeeHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Tenancy\Environment;
use Tenancy\Identification\Contracts\ResolvesTenants;

class UpdateEmployeeHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:update_employee_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for old salaries and set new currency rate';

    protected $resolver;

    protected EmployeeRepositoryInterface $employeeRepository;

    /**
     * Create a new command instance.
     *
     * @param EmployeeRepositoryInterface $employeeRepository
     */
    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct();
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rates = getCurrencyRates();
        $rate = $rates['rates']['USD'];

        $companies = Company::all();
        $companies->each(function ($company) use ($rates, $rate) {
            $euro = false;
            $environment = app(Environment::class);
            $environment->setTenant($company);
            if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                $euro = true;
            }
            $oldSalaries = EmployeeHistory::where([['end_date', null], ['updated_at','<', Carbon::now()->subYear()]])->get();
            if ($oldSalaries) {
                $oldSalaries->each(function ($salary) use ($euro, $rates, $rate) {
                    $employee = $this->employeeRepository->firstById($salary->employee_id);
                    $employeeCurrency = CurrencyCode::make((int)$employee->default_currency)->__toString();
                    $employeeRate = $rates['rates'][$employeeCurrency];
                    $salary->end_date = now();
                    EmployeeHistory::create([
                    'employee_id' => $salary->employee_id,
                    'salary' => ceiling($employee->salary * (1/$employeeRate), 2),
                    'salary_usd' => ceiling($employee->salary * (1/$employeeRate) * $rate, 2),
                    'start_date' => now(),
                    'end_date' => null,
                    'currency_rate' => $euro ? $rate : 1/$rate
                    ]);
                    $salary->save();
                });
            }
        });

        return 0;
    }
}
