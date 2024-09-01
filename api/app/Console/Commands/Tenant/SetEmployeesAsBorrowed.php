<?php

namespace App\Console\Commands\Tenant;

use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Models\Company;
use App\Models\Employee;
use Exception;
use Illuminate\Console\Command;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;

class SetEmployeesAsBorrowed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:set_as_borrowed {name} {value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a company his employees as borrowable or not,
                              use the name of the company followed by on or off';

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
        $companyName = $this->argument('name');
        $value = $this->argument('value');
        if ($value == 'on') {
            $toggle = true;
            $message = 'Company ' . $companyName . ' his employees can be borrowed now.';
        } elseif ($value == 'off') {
            $toggle = false;
            $message = 'Company ' . $companyName . ' his employees can no longer be borrowed.';
        } else {
            $this->line('You can only use on or off.');
            return 1;
        }

        $company = Company::where('name', $companyName)->first();

        if ($company) {
            Tenancy::setTenant($company);
            $employees = $this->employeeRepository->getAll()->pluck('id')->toarray();
            $this->employeeRepository->massUpdate($employees, ['can_be_borrowed' => $toggle]);
            $indexes = indices();
            foreach ($indexes as $model) {
                if (in_array(OnTenant::class, class_uses_recursive($model)) && $model == Employee::class) {
                    try {
                        $model::deleteIndex();
                    } catch (Exception $e) {
                        $this->line('Could not deleted index of ' . $model . '. Reason: ' . $e->getMessage());
                    }
                    $model::createIndex();
                    $model::addAllToIndexWithoutScopes();
                    $this->line('<fg=green>Refreshed index for model</> <fg=green;bg=black;options=bold>' . $model . '</>');
                }
            }
            $this->line($message);
        } else {
            $this->line('Company ' . $companyName . ' not found');
        }

        return 0;
    }
}
