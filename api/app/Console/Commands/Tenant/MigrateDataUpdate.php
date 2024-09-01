<?php

namespace App\Console\Commands\Tenant;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateDataUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate_update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'appy migrations and migrate a tenant database by using --all for all tenants and refresh ES indexes';

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
        $this->info('Applying migrations...');
        $this->line(Artisan::output());

        Artisan::call('migrate --force --no-interaction');
        $this->line(Artisan::output());

        $this->info('Migrating tenant databases...');
        $this->line(Artisan::output());

        Artisan::call('tenant:migrate_tenant --all');
        $this->line(Artisan::output());

        return 0;
    }
}
