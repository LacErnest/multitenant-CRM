<?php

namespace App\Console\Commands\Tenant;

use App\Models\Company;
use Illuminate\Console\Command;

class DeleteTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete_tenant {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a company tenant';

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
        $company_name = $this->argument('name');

        $company = Company::where('name', $company_name)->first();
        if ($company) {
            $company->delete();
            $this->line('Company ' . $company_name . ' deleted successfully');
        } else {
            $this->line('Company ' . $company_name . ' not found');
        }
    }
}
