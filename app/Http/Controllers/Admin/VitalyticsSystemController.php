<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\App;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class VitalyticsSystemController extends Controller
{
    /**
     * Display the system dashboard
     */
    public function index()
    {
        return view('admin.vitalytics.index', [
            'overview' => $this->getOverviewStats(),
            'database' => $this->getDatabaseStats(),
            'queue' => $this->getQueueStats(),
            'system' => $this->getSystemStats(),
            'memory' => $this->getMemoryInfo(),
            'oomEvents' => $this->getOomEvents(),
            'tables' => $this->getTableStats(),
            'recentFeedback' => $this->getRecentFeedback(),
            'topMemoryProcesses' => $this->getTopMemoryProcesses(),
            'topCpuProcesses' => $this->getTopCpuProcesses(),
            'failedJobs' => $this->getFailedJobsList(),
        ]);
    }

    /**
     * Get overview statistics
     */
    protected function getOverviewStats(): array
    {
        return [
            'total_health_events' => DB::table('health_events')->count(),
            'total_analytics_events' => DB::table('analytics_events')->count(),
            'total_sessions' => DB::table('analytics_sessions')->count(),
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_apps' => App::count(),
            'active_apps' => App::where('is_active', true)->count(),
            'events_today' => DB::table('health_events')
                ->whereDate('event_timestamp', today())
                ->count(),
            'analytics_today' => DB::table('analytics_events')
                ->whereDate('event_timestamp', today())
                ->count(),
            'sessions_today' => DB::table('analytics_sessions')
                ->whereDate('started_at', today())
                ->count(),
        ];
    }

    /**
     * Get database statistics
     */
    protected function getDatabaseStats(): array
    {
        try {
            $dbName = config('database.connections.mysql.database');

            // Get total database size
            $sizeResult = DB::select("
                SELECT
                    SUM(data_length + index_length) as total_size,
                    SUM(data_length) as data_size,
                    SUM(index_length) as index_size
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            $totalSize = $sizeResult[0]->total_size ?? 0;
            $dataSize = $sizeResult[0]->data_size ?? 0;
            $indexSize = $sizeResult[0]->index_size ?? 0;

            // Get table count
            $tableCount = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName])[0]->count ?? 0;

            // Get connection info
            $connectionInfo = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $activeConnections = (int)($connectionInfo[0]->Value ?? 0);

            // Get max connections
            $maxConnectionsResult = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            $maxConnections = (int)($maxConnectionsResult[0]->Value ?? 151);
            $connectionUsagePercent = $maxConnections > 0 ? round(($activeConnections / $maxConnections) * 100, 1) : 0;

            // Get MySQL version
            $versionInfo = DB::select("SELECT VERSION() as version");
            $mysqlVersion = $versionInfo[0]->version ?? 'Unknown';

            // Get database uptime
            $uptimeResult = DB::select("SHOW STATUS LIKE 'Uptime'");
            $uptimeSeconds = (int)($uptimeResult[0]->Value ?? 0);
            $dbUptime = $this->formatUptime($uptimeSeconds);

            // Get query statistics
            $questionsResult = DB::select("SHOW STATUS LIKE 'Questions'");
            $totalQueries = (int)($questionsResult[0]->Value ?? 0);

            $slowQueriesResult = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = (int)($slowQueriesResult[0]->Value ?? 0);

            // Get queries per second (approximate)
            $queriesPerSecond = $uptimeSeconds > 0 ? round($totalQueries / $uptimeSeconds, 1) : 0;

            // Get buffer pool info (InnoDB)
            $bufferPoolResult = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool_read_requests'");
            $bufferPoolReads = (int)($bufferPoolResult[0]->Value ?? 0);
            $bufferPoolMissResult = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool_reads'");
            $bufferPoolMisses = (int)($bufferPoolMissResult[0]->Value ?? 0);
            $bufferPoolHitRate = $bufferPoolReads > 0 ? round((($bufferPoolReads - $bufferPoolMisses) / $bufferPoolReads) * 100, 2) : 100;

            // Connection status
            $isConnected = true;

            return [
                'connected' => $isConnected,
                'total_size' => $this->formatBytes($totalSize),
                'total_size_raw' => $totalSize,
                'data_size' => $this->formatBytes($dataSize),
                'index_size' => $this->formatBytes($indexSize),
                'table_count' => $tableCount,
                'active_connections' => $activeConnections,
                'max_connections' => $maxConnections,
                'connection_usage_percent' => $connectionUsagePercent,
                'mysql_version' => $mysqlVersion,
                'database_name' => $dbName,
                'uptime' => $dbUptime,
                'uptime_seconds' => $uptimeSeconds,
                'total_queries' => number_format($totalQueries),
                'slow_queries' => number_format($slowQueries),
                'queries_per_second' => $queriesPerSecond,
                'buffer_pool_hit_rate' => $bufferPoolHitRate,
                'healthy' => $connectionUsagePercent < 80 && $bufferPoolHitRate > 95,
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'total_size' => 'N/A',
                'data_size' => 'N/A',
                'index_size' => 'N/A',
                'table_count' => 0,
                'active_connections' => 0,
                'max_connections' => 0,
                'connection_usage_percent' => 0,
                'mysql_version' => 'Unknown',
                'database_name' => config('database.connections.mysql.database'),
                'uptime' => 'N/A',
                'uptime_seconds' => 0,
                'total_queries' => '0',
                'slow_queries' => '0',
                'queries_per_second' => 0,
                'buffer_pool_hit_rate' => 0,
                'healthy' => false,
            ];
        }
    }

    /**
     * Get individual table statistics
     */
    protected function getTableStats(): array
    {
        try {
            $dbName = config('database.connections.mysql.database');

            $tables = DB::select("
                SELECT
                    table_name,
                    table_rows,
                    data_length,
                    index_length,
                    (data_length + index_length) as total_size
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 15
            ", [$dbName]);

            return collect($tables)->map(function ($table) {
                return [
                    'name' => $table->table_name,
                    'rows' => number_format($table->table_rows ?? 0),
                    'rows_raw' => $table->table_rows ?? 0,
                    'size' => $this->formatBytes($table->total_size ?? 0),
                    'size_raw' => $table->total_size ?? 0,
                    'data_size' => $this->formatBytes($table->data_length ?? 0),
                    'index_size' => $this->formatBytes($table->index_length ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get queue statistics
     */
    protected function getQueueStats(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            // Get jobs processed today (if we have a jobs table with timestamps)
            $recentFailed = DB::table('failed_jobs')
                ->whereDate('failed_at', today())
                ->count();

            // Get oldest pending job
            $oldestJob = DB::table('jobs')->orderBy('created_at')->first();
            $oldestJobAge = $oldestJob
                ? now()->diffForHumans(\Carbon\Carbon::createFromTimestamp($oldestJob->created_at), true)
                : null;

            return [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'failed_today' => $recentFailed,
                'oldest_job_age' => $oldestJobAge,
                'queue_healthy' => $pendingJobs < 100 && $failedJobs < 10,
            ];
        } catch (\Exception $e) {
            return [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_today' => 0,
                'oldest_job_age' => null,
                'queue_healthy' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system statistics
     */
    protected function getSystemStats(): array
    {
        // Disk space
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskUsedPercent = round(($diskUsed / $diskTotal) * 100, 1);

        // Memory (if available)
        $memoryInfo = $this->getMemoryInfo();

        // CPU info
        $cpuInfo = $this->getCpuInfo();

        // PHP info
        $phpVersion = phpversion();

        // Laravel version
        $laravelVersion = app()->version();

        // Server info
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $hostname = gethostname();

        // Uptime (Linux only)
        $uptime = $this->getUptime();

        // Load average (Linux only)
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : null;

        return [
            'disk_total' => $this->formatBytes($diskTotal),
            'disk_free' => $this->formatBytes($diskFree),
            'disk_used' => $this->formatBytes($diskUsed),
            'disk_used_percent' => $diskUsedPercent,
            'disk_healthy' => $diskUsedPercent < 85,
            'memory_total' => $memoryInfo['total'] ?? 'N/A',
            'memory_used' => $memoryInfo['used'] ?? 'N/A',
            'memory_free' => $memoryInfo['free'] ?? 'N/A',
            'memory_used_percent' => $memoryInfo['used_percent'] ?? 0,
            'memory_healthy' => ($memoryInfo['used_percent'] ?? 0) < 85,
            'cpu_used_percent' => $cpuInfo['used_percent'] ?? 0,
            'cpu_cores' => $cpuInfo['cores'] ?? 1,
            'cpu_healthy' => ($cpuInfo['used_percent'] ?? 0) < 85,
            'php_version' => $phpVersion,
            'laravel_version' => $laravelVersion,
            'server_software' => $serverSoftware,
            'hostname' => $hostname,
            'uptime' => $uptime,
            'load_average' => $loadAvg ? implode(', ', array_map(fn($v) => round($v, 2), $loadAvg)) : 'N/A',
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Get memory information from /proc/meminfo (Linux)
     */
    protected function getMemoryInfo(): array
    {
        if (!is_readable('/proc/meminfo')) {
            return [];
        }

        try {
            $meminfo = file_get_contents('/proc/meminfo');

            // Parse all memory values
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availableMatch);
            preg_match('/MemFree:\s+(\d+)/', $meminfo, $freeMatch);
            preg_match('/Buffers:\s+(\d+)/', $meminfo, $buffersMatch);
            preg_match('/Cached:\s+(\d+)/', $meminfo, $cachedMatch);
            preg_match('/SwapTotal:\s+(\d+)/', $meminfo, $swapTotalMatch);
            preg_match('/SwapFree:\s+(\d+)/', $meminfo, $swapFreeMatch);

            $total = isset($totalMatch[1]) ? $totalMatch[1] * 1024 : 0;
            $available = isset($availableMatch[1]) ? $availableMatch[1] * 1024 : 0;
            $free = isset($freeMatch[1]) ? $freeMatch[1] * 1024 : 0;
            $buffers = isset($buffersMatch[1]) ? $buffersMatch[1] * 1024 : 0;
            $cached = isset($cachedMatch[1]) ? $cachedMatch[1] * 1024 : 0;
            $swapTotal = isset($swapTotalMatch[1]) ? $swapTotalMatch[1] * 1024 : 0;
            $swapFree = isset($swapFreeMatch[1]) ? $swapFreeMatch[1] * 1024 : 0;

            $used = $total - $available;
            $swapUsed = $swapTotal - $swapFree;
            $swapUsedPercent = $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 1) : 0;
            $usedPercent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            // Memory pressure level: low/medium/high/critical
            $pressureLevel = 'low';
            if ($usedPercent >= 90 || ($swapTotal > 0 && $swapUsedPercent >= 80)) {
                $pressureLevel = 'critical';
            } elseif ($usedPercent >= 80 || ($swapTotal > 0 && $swapUsedPercent >= 50)) {
                $pressureLevel = 'high';
            } elseif ($usedPercent >= 70) {
                $pressureLevel = 'medium';
            }

            return [
                'total' => $this->formatBytes($total),
                'total_raw' => $total,
                'used' => $this->formatBytes($used),
                'used_raw' => $used,
                'free' => $this->formatBytes($available),
                'free_raw' => $available,
                'used_percent' => $usedPercent,
                'buffers' => $this->formatBytes($buffers),
                'cached' => $this->formatBytes($cached),
                'buffers_cached' => $this->formatBytes($buffers + $cached),
                // Swap information
                'swap_total' => $this->formatBytes($swapTotal),
                'swap_total_raw' => $swapTotal,
                'swap_used' => $this->formatBytes($swapUsed),
                'swap_used_raw' => $swapUsed,
                'swap_free' => $this->formatBytes($swapFree),
                'swap_used_percent' => $swapUsedPercent,
                'swap_enabled' => $swapTotal > 0,
                // Memory pressure assessment
                'pressure_level' => $pressureLevel,
                'oom_risk' => $swapTotal === 0 && $usedPercent >= 80,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent OOM killer events from system logs
     */
    protected function getOomEvents(): array
    {
        try {
            // Check dmesg for OOM killer events
            $output = shell_exec('dmesg 2>/dev/null | grep -i "out of memory\|oom-kill\|killed process" | tail -10');
            if (!$output) {
                return [];
            }

            $events = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                // Parse the dmesg line
                // Example: [12345.678] Out of memory: Killed process 1234 (php-fpm)
                $event = [
                    'raw' => \Illuminate\Support\Str::limit($line, 200),
                    'timestamp' => null,
                    'process' => null,
                    'pid' => null,
                ];

                // Extract timestamp
                if (preg_match('/\[\s*([\d.]+)\]/', $line, $matches)) {
                    $event['timestamp'] = $matches[1];
                }

                // Extract killed process info
                if (preg_match('/Killed process (\d+) \(([^)]+)\)/', $line, $matches)) {
                    $event['pid'] = $matches[1];
                    $event['process'] = $matches[2];
                }

                $events[] = $event;
            }

            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get CPU information from /proc/stat (Linux)
     */
    protected function getCpuInfo(): array
    {
        try {
            // Get number of CPU cores
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuInfo, $matches);
            $cores = count($matches[0]) ?: 1;

            // Get CPU usage by sampling /proc/stat twice
            $stat1 = $this->parseCpuStat();
            usleep(100000); // 100ms sample
            $stat2 = $this->parseCpuStat();

            if ($stat1 && $stat2) {
                $diffIdle = $stat2['idle'] - $stat1['idle'];
                $diffTotal = $stat2['total'] - $stat1['total'];
                $usedPercent = $diffTotal > 0 ? round((1 - ($diffIdle / $diffTotal)) * 100, 1) : 0;
            } else {
                $usedPercent = 0;
            }

            return [
                'cores' => $cores,
                'used_percent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return ['cores' => 1, 'used_percent' => 0];
        }
    }

    /**
     * Parse /proc/stat for CPU times
     */
    protected function parseCpuStat(): ?array
    {
        if (!is_readable('/proc/stat')) {
            return null;
        }

        $stat = file_get_contents('/proc/stat');
        $lines = explode("\n", $stat);

        foreach ($lines as $line) {
            if (str_starts_with($line, 'cpu ')) {
                $parts = preg_split('/\s+/', trim($line));
                // cpu user nice system idle iowait irq softirq steal guest guest_nice
                $user = (int)($parts[1] ?? 0);
                $nice = (int)($parts[2] ?? 0);
                $system = (int)($parts[3] ?? 0);
                $idle = (int)($parts[4] ?? 0);
                $iowait = (int)($parts[5] ?? 0);
                $irq = (int)($parts[6] ?? 0);
                $softirq = (int)($parts[7] ?? 0);
                $steal = (int)($parts[8] ?? 0);

                $idleTotal = $idle + $iowait;
                $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq + $steal;

                return [
                    'idle' => $idleTotal,
                    'total' => $total,
                ];
            }
        }

        return null;
    }

    /**
     * Get system uptime (Linux)
     */
    protected function getUptime(): string
    {
        if (!is_readable('/proc/uptime')) {
            return 'N/A';
        }

        try {
            $uptime = (float) explode(' ', file_get_contents('/proc/uptime'))[0];
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);

            $parts = [];
            if ($days > 0) $parts[] = "{$days}d";
            if ($hours > 0) $parts[] = "{$hours}h";
            if ($minutes > 0) $parts[] = "{$minutes}m";

            return implode(' ', $parts) ?: '< 1m';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get recent feedback submissions
     */
    protected function getRecentFeedback(): array
    {
        try {
            return Feedback::with('user:id,name,email')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($feedback) {
                    return [
                        'id' => $feedback->id,
                        'feedback_id' => $feedback->feedback_id,
                        'type' => $feedback->type,
                        'message' => \Illuminate\Support\Str::limit($feedback->message, 100),
                        'status' => $feedback->status,
                        'user_name' => $feedback->user?->name ?? 'Unknown',
                        'user_email' => $feedback->user?->email ?? '',
                        'created_at' => $feedback->created_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get total feedback count
     */
    protected function getFeedbackCount(): int
    {
        try {
            return Feedback::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get top memory-consuming processes
     */
    protected function getTopMemoryProcesses(): array
    {
        try {
            // Use ps command to get top memory processes
            $output = shell_exec('ps aux --sort=-%mem | head -4 | tail -3');
            if (!$output) {
                return [];
            }

            $processes = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', $line, 11);
                if (count($parts) >= 11) {
                    $processes[] = [
                        'user' => $parts[0],
                        'pid' => $parts[1],
                        'cpu' => $parts[2],
                        'memory' => $parts[3],
                        'vsz' => $this->formatBytes((int)$parts[4] * 1024),
                        'rss' => $this->formatBytes((int)$parts[5] * 1024),
                        'command' => \Illuminate\Support\Str::limit($parts[10], 50),
                    ];
                }
            }

            return $processes;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get top CPU-consuming processes
     */
    protected function getTopCpuProcesses(): array
    {
        try {
            // Use ps command to get top CPU processes
            $output = shell_exec('ps aux --sort=-%cpu | head -4 | tail -3');
            if (!$output) {
                return [];
            }

            $processes = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', $line, 11);
                if (count($parts) >= 11) {
                    $processes[] = [
                        'user' => $parts[0],
                        'pid' => $parts[1],
                        'cpu' => $parts[2],
                        'memory' => $parts[3],
                        'command' => \Illuminate\Support\Str::limit($parts[10], 50),
                    ];
                }
            }

            return $processes;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get list of failed jobs
     */
    protected function getFailedJobsList(): array
    {
        try {
            return DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->take(20)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $jobName = $payload['displayName'] ?? 'Unknown Job';

                    // Extract short exception message
                    $exception = $job->exception;
                    $exceptionLines = explode("\n", $exception);
                    $shortException = \Illuminate\Support\Str::limit($exceptionLines[0] ?? '', 100);

                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'job_name' => class_basename($jobName),
                        'queue' => $job->queue,
                        'failed_at' => \Carbon\Carbon::parse($job->failed_at),
                        'exception' => $shortException,
                        'full_exception' => $exception,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format uptime seconds to human readable
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '< 1m';
    }
}
