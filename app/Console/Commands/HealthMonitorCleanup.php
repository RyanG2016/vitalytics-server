<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HealthEvent;
use Illuminate\Support\Facades\DB;

class HealthMonitorCleanup extends Command
{
    protected $signature = 'vitalytics:cleanup
                            {--days= : Override retention days from config}
                            {--dry-run : Show what would be deleted without deleting}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Clean up old health events based on retention policy';

    public function handle(): int
    {
        $retentionDays = $this->option('days')
            ?? config('vitalytics.retention_days', 90);

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $cutoffDate = now()->subDays($retentionDays);

        $this->info("Vitalytics Cleanup");
        $this->line("Retention: {$retentionDays} days");
        $this->line("Cutoff date: {$cutoffDate->toDateTimeString()}");

        if ($dryRun) {
            $this->warn('DRY RUN - No data will be deleted');
        }

        $this->line('');

        // Count events to delete
        $eventCount = HealthEvent::where('event_timestamp', '<', $cutoffDate)->count();
        $this->line("Events to delete: {$eventCount}");

        // Count stats to delete
        $statsCount = DB::table('health_stats')
            ->where('date', '<', $cutoffDate->toDateString())
            ->count();
        $this->line("Stats records to delete: {$statsCount}");

        if ($eventCount === 0 && $statsCount === 0) {
            $this->info('Nothing to clean up.');
            return 0;
        }

        if ($dryRun) {
            $this->line('');
            $this->info('Run without --dry-run to delete these records.');
            return 0;
        }

        if (!$force && !$this->confirm('Proceed with deletion?')) {
            $this->line('Cancelled.');
            return 0;
        }

        // Delete in batches to avoid memory issues
        $this->info('Deleting events...');
        $deleted = 0;

        do {
            $batch = HealthEvent::where('event_timestamp', '<', $cutoffDate)
                ->limit(1000)
                ->delete();
            $deleted += $batch;

            if ($batch > 0) {
                $this->line("  Deleted {$deleted} events...");
            }
        } while ($batch > 0);

        // Delete old stats
        $this->info('Deleting stats...');
        $statsDeleted = DB::table('health_stats')
            ->where('date', '<', $cutoffDate->toDateString())
            ->delete();

        $this->line('');
        $this->info("Cleanup complete!");
        $this->line("  Events deleted: {$deleted}");
        $this->line("  Stats deleted: {$statsDeleted}");

        return 0;
    }
}
