<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tenancy\Environment;

class fix_month_may_bug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:fix_month_may_bug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fix a bug in assigning employees in the month may';

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
        $companies = Company::all();
        foreach ($companies as $company) {
            $environment = app(Environment::class);
            $environment->setTenant($company);

            DB::beginTransaction();
            try {
                $affected = DB::connection('tenant')->table('project_employees')
                ->where('month', '1970-01-01')
                ->update(['month' => '2022-05-01']);

                DB::commit();
                $this->line('Changed ' . $affected . ' assigned dates for company ' . $company->name);
            } catch (\Throwable $e) {
                $this->line($e->getMessage());
                DB::rollBack();
                $this->line('An error occurred. Rolled back.');
            }
        }

        return 0;
    }
}
