<?php

namespace App\Console;

use App\Console\Commands\Tenant\DeleteImportFiles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        DeleteImportFiles::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        if ($this->app->environment('production')) {
            $schedule->command('scheduler:fetch_currency_rates')->daily()->at('00:30');
            $schedule->command('scheduler:update_employee_history')->daily()->at('00:45');

            $schedule->command('backup:clean')->daily()->at('01:00');
            $schedule->command('maintenance:backup_tenants')->daily()->at('02:00');
            $schedule->command('backup:monitor')->daily()->at('03:00');
        }

        $schedule->command('scheduler:settings_number month')->monthly();
        $schedule->command('scheduler:settings_number year')->yearly();
        $schedule->command('scheduler:settings_number quarter')->quarterly();

        $schedule->command('scheduler:get_xero_entities')->hourly();
        $schedule->command('scheduler:refresh_xero_tokens')->monthly();
        $schedule->command('tenant:imports_delete')->daily()->at('04:00');
        $schedule->command('scheduler:invoice_notifications')->daily()->at('04:15');

        $schedule->command('server-monitor:run-checks')->withoutOverlapping()->everyMinute();

        $schedule->command('scheduler:quote_notifications')->daily()->at('04:30');

        $schedule->command('scheduler:monthly_quote_notifications')->monthlyOn(1, '04:35');
        $schedule->command('scheduler:approve_earn_out_notifications')->cron('0 8 20 */3 *');
        ;
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
