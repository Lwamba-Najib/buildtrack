<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // Lock dormant users daily
        $schedule->command('users:lock-dormant')->daily();

        // Run backups only in non-production environments
        if (env('APP_ENV') !== 'production') {
            // Run backup every day at midnight and noon
            $schedule->command('backup:run')->twiceDaily(0, 12);
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
