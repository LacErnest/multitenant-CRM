<?php

namespace App\Console\Commands\Maintenance;

use App\Services\BackupJobFactory;
use Exception;
use Illuminate\Console\Command;
use Spatie\Backup\Commands\BackupCommand as SpatieBackupCommand;
use Spatie\Backup\Events\BackupHasFailed;

class BackupTenants extends SpatieBackupCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:backup_tenants {--disable-notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make backup of tenant databases';

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
        consoleOutput()->comment('Starting backup...');

        $disableNotifications = $this->option('disable-notifications');

        try {
            $backupJob = BackupJobFactory::createFromArray(config('backup'));

            if ($disableNotifications) {
                $backupJob->disableNotifications();
            }

            $backupJob->run();

            consoleOutput()->comment('Backup completed!');
        } catch (Exception $exception) {
            consoleOutput()->error("Backup failed because: {$exception->getMessage()}.");

            if (!$disableNotifications) {
                event(new BackupHasFailed($exception));
            }

            return 1;
        }
    }
}
