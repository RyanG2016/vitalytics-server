<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsDailyStat extends Model
{
    protected $fillable = [
        'date',
        'app_identifier',
        'event_name',
        'event_category',
        'event_count',
        'unique_sessions',
        'unique_devices',
        'is_test',
    ];

    protected $casts = [
        'date' => 'date',
        'is_test' => 'boolean',
        'event_count' => 'integer',
        'unique_sessions' => 'integer',
        'unique_devices' => 'integer',
    ];

    /**
     * Scope: Production stats only
     */
    public function scopeProduction($query)
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope: Test stats only
     */
    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope: Filter by app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Aggregate stats for a given date
     */
    public static function aggregateForDate(Carbon $date, bool $isTest = false): int
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get aggregated data from analytics_events
        $stats = AnalyticsEvent::query()
            ->where('is_test', $isTest)
            ->whereBetween('event_timestamp', [$startOfDay, $endOfDay])
            ->select([
                'app_identifier',
                'event_name',
                'event_category',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT session_id) as unique_sessions'),
                DB::raw('COUNT(DISTINCT device_id) as unique_devices'),
            ])
            ->groupBy('app_identifier', 'event_name', 'event_category')
            ->get();

        $count = 0;
        foreach ($stats as $stat) {
            self::updateOrCreate(
                [
                    'date' => $date->toDateString(),
                    'app_identifier' => $stat->app_identifier,
                    'event_name' => $stat->event_name,
                    'is_test' => $isTest,
                ],
                [
                    'event_category' => $stat->event_category,
                    'event_count' => $stat->event_count,
                    'unique_sessions' => $stat->unique_sessions,
                    'unique_devices' => $stat->unique_devices,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Get top events for an app
     */
    public static function getTopEvents(
        string $appIdentifier,
        Carbon $startDate,
        Carbon $endDate,
        bool $isTest = false,
        int $limit = 10
    ): \Illuminate\Support\Collection {
        return self::forApp($appIdentifier)
            ->where('is_test', $isTest)
            ->dateRange($startDate, $endDate)
            ->select([
                'event_name',
                'event_category',
                DB::raw('SUM(event_count) as total_count'),
                DB::raw('SUM(unique_sessions) as total_sessions'),
                DB::raw('SUM(unique_devices) as total_devices'),
            ])
            ->groupBy('event_name', 'event_category')
            ->orderByDesc('total_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get daily trend for an app
     */
    public static function getDailyTrend(
        string $appIdentifier,
        Carbon $startDate,
        Carbon $endDate,
        bool $isTest = false
    ): \Illuminate\Support\Collection {
        return self::forApp($appIdentifier)
            ->where('is_test', $isTest)
            ->dateRange($startDate, $endDate)
            ->select([
                'date',
                DB::raw('SUM(event_count) as total_events'),
                DB::raw('SUM(unique_sessions) as total_sessions'),
                DB::raw('SUM(unique_devices) as total_devices'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
