<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
         // $schedule->command('ingest:weather')->cron('* * * * *');
        // $schedule->command('ingest:weather')->cron('0 * * * *');
        // $schedule->command('ingest:weather')->cron('0 */2 * * *');
        $schedule->command('ingest:weather')->cron('0 */3 * * *');
        // run ingest:weather every 3 hours
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
