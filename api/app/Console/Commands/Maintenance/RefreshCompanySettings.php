<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Setting;
use App\Services\BackupJobFactory;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Commands\BackupCommand as SpatieBackupCommand;
use Spatie\Backup\Events\BackupHasFailed;
use Tenancy\Facades\Tenancy;

class RefreshCompanySettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:refresh_settings {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create company settings if empty';

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
        $force = $this->option('force');
        if ($company_name) {
            $company = Company::where('name', $company_name)->first();
            if ($company) {
                Tenancy::setTenant($company);
                if (!Setting::first() || $force) {
                    Artisan::call('db:seed', ['--class'=>'SettingSeeder']);
                    $this->line('Settings created successfully.');
                } else {
                    $this->line('Settings already exists.');
                }
            } else {
                $this->line('Company not found. You need to specify a valid name for the company.');
            }
        } else {
            $this->line('You need to specify a name for the company.');
        }
        return 0;
    }
}
