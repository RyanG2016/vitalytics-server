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
        // Note: CheckHealthAlerts is dispatched in real-time when events are received via API
        // No need for scheduled alert checks - they happen on event ingestion

        // Aggregate analytics data daily at 1 AM (before cleanup)
        $schedule->command('analytics:aggregate')
            ->dailyAt('01:00')
            ->name('vitalytics-analytics-aggregate')
            ->withoutOverlapping();

        // Clean up old events daily at 2 AM
        $schedule->command('vitalytics:cleanup --force')
            ->dailyAt('02:00')
            ->name('vitalytics-cleanup')
            ->withoutOverlapping();

        // Check for missing heartbeats every 5 minutes
        $schedule->command('heartbeats:check')
            ->everyFiveMinutes()
            ->name('vitalytics-heartbeat-check')
            ->withoutOverlapping();

        // Generate daily AI health analysis (runs every hour, checks which products are due)
        $schedule->command('analysis:daily')
            ->hourly()
            ->name('vitalytics-ai-health-analysis')
            ->withoutOverlapping();

        // Generate daily AI analytics analysis (runs every hour, 1 hour after health)
        $schedule->command('analysis:analytics')
            ->hourly()
            ->name('vitalytics-ai-analytics-analysis')
            ->withoutOverlapping();
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
