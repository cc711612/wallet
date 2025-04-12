<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:daily_update_exchange_rate')->hourly();
        $schedule->command('telescope:prune --hours=48')->daily();
        $schedule->command('cache:clear')->weeklyOn('4');
        $schedule->command('backup:run --only-db --disable-notifications')->daily();
        $schedule->command('backup:clean --disable-notifications')->daily();
        $schedule->command('command:auto_create_wallet')->monthlyOn('1');
        if (config('app.env') == 'production') {
            $schedule->command('command:auto_calculate_wallet')->monthlyOn('1');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
