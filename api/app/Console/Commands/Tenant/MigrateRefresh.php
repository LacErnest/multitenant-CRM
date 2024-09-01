<?php

namespace App\Console\Commands\Tenant;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class MigrateRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate_refresh {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all tenant migrations';

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
        if (Schema::hasTable('companies')) {
            $companies = Company::all();
            if ($companies) {
                $companies->each(function ($company) {
                    $company->delete();
                });
            }
        }

        if ($this->option('force') && $this->option('no-interaction')) {
            Artisan::call('migrate:fresh --seed --force --no-interaction');
        } elseif ($this->option('force')) {
            Artisan::call('migrate:fresh --seed --force');
        } elseif ($this->option('no-interaction')) {
            Artisan::call('migrate:fresh --seed --no-interaction');
        } else {
            Artisan::call('migrate:fresh --seed');
        }

        return 0;
    }
}
