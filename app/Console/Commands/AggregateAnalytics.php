<?php

namespace App\Console\Commands;

use App\Models\AnalyticsDailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate
                            {--date= : Specific date to aggregate (YYYY-MM-DD)}
                            {--days=1 : Number of days to process (default: 1, yesterday)}
                            {--include-test : Also aggregate test data}';

    protected $description = 'Aggregate analytics events into daily statistics';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $includeTest = $this->option('include-test');

        // If specific date provided, use that
        if ($dateString = $this->option('date')) {
            try {
                $date = Carbon::parse($dateString);
                $this->aggregateDate($date, $includeTest);
                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Invalid date format: {$dateString}");
                return Command::FAILURE;
            }
        }

        // Otherwise process last N days
        $this->info("Aggregating analytics for the last {$days} day(s)...");

        for ($i = 1; $i <= $days; $i++) {
            $date = Carbon::now()->subDays($i);
            $this->aggregateDate($date, $includeTest);
        }

        $this->info("\nAggregation complete!");
        return Command::SUCCESS;
    }

    protected function aggregateDate(Carbon $date, bool $includeTest = false): void
    {
        $dateString = $date->toDateString();
        $this->line("\nProcessing: {$dateString}");

        // Aggregate production data
        try {
            $prodCount = AnalyticsDailyStat::aggregateForDate($date, false);
            $this->info("  ✓ Production stats: {$prodCount} records");
        } catch (\Exception $e) {
            $this->error("  ✗ Production aggregation failed: " . $e->getMessage());
            Log::error("Analytics aggregation failed for {$dateString} (production)", [
                'error' => $e->getMessage(),
            ]);
        }

        // Optionally aggregate test data
        if ($includeTest) {
            try {
                $testCount = AnalyticsDailyStat::aggregateForDate($date, true);
                $this->info("  ✓ Test stats: {$testCount} records");
            } catch (\Exception $e) {
                $this->error("  ✗ Test aggregation failed: " . $e->getMessage());
                Log::error("Analytics aggregation failed for {$dateString} (test)", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
