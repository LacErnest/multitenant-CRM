<?php

namespace App\Console\Commands\Dev;

use App\Jobs\CreateProjectJob;
use App\Models\Company;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Tenancy\Facades\Tenancy;

class GenerateCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:generate_commissions {name?} {--n=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate More factories Commissions';

    /**
     * @var int
     */
    private $defaultCommissionNumber = 1000;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $companies = $this->getCompanies();
        $maxNumber = $this->getCommissionNumber();

        foreach ($companies as $company) {
            $customers = Customer::where('company_id', $company->id)->get();
            Tenancy::setTenant($company);

            for ($x = 0; $x < $maxNumber; $x++) {
                $customer = $customers->random(1)->first();
                CreateProjectJob::dispatch($customer, $company, 1, Carbon::parse());
            }
            $this->line('creating '.$maxNumber.' commissions for ' . $company->name);
        }

        return 0;
    }

    /**
     * Get allowed companies
     * @return Collection
     */
    private function getCompanies()
    {
        if ($this->option('all')) {
            return Company::all();
        }
        $company_name = $this->option('name');
        $company = Company::where('name', $company_name)->first();
        if ($company) {
            return collect([$company]);
        }
        $this->line('Invalid company name');
        return collect();
    }

    /**
     * Get commissions number for generations
     * @return int
     */
    private function getCommissionNumber()
    {
        return $this->option('n') ?? $this->defaultCommissionNumber;
    }
}
