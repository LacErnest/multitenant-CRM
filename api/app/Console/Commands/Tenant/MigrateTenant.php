<?php

namespace App\Console\Commands\Tenant;

use App\Models\Company;
use Illuminate\Console\Command;

class MigrateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate_tenant {name?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate a tenant database by tenant name, use --all for all tenants';

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
        if ($this->option('all')) {
            $companies = Company::all();
            foreach ($companies as $company) {
                event(new \Tenancy\Tenant\Events\Updated($company));
                $this->line('Company ' . $company->name . ' updated successfully');
            }
        } else {
            $company_name = $this->argument('name');
            $company = Company::where('name', $company_name)->first();
            if ($company) {
                event(new \Tenancy\Tenant\Events\Updated($company));
                $this->line('Company ' . $company_name . ' updated successfully');
            } else {
                $this->line('Company ' . $company_name . ' not found');
            }
        }

        return 0;
    }
}
