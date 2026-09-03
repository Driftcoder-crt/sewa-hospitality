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
        // Database queue worker - runs every minute on shared hosting
        $schedule->command('queue:work --stop-when-empty --tries=3')
            ->everyMinute()
            ->withoutOverlapping(60)
            ->onOneServer();

        // Pulse metrics aggregation
        $schedule->command('pulse:aggregate')->everyFiveMinutes()->onOneServer();
        $schedule->command('pulse:check')->everyFiveMinutes()->onOneServer();

        // Sitemap regeneration (daily at 2 AM)
        $schedule->command('sitemap:generate')->dailyAt('02:00');

        // Google Business Profile review sync (daily at 3 AM)
        $schedule->command('reviews:sync')->dailyAt('03:00');

        // AI budget reset (daily at midnight)
        $schedule->command('ai:reset-budgets')->daily();

        // Session cleanup
        $schedule->command('session:table')->daily();
        $schedule->command('db:prune-sessions')->daily();

        // Failed jobs cleanup (weekly)
        $schedule->command('queue:prune-batches')->weekly();
        $schedule->command('queue:prune-failed')->weekly();
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
