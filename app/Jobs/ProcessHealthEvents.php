<?php

namespace App\Jobs;

use App\Models\HealthEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProcessHealthEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private array $batch
    ) {}

    public function handle(): void
    {
        $deviceInfo = $this->batch['deviceInfo'];
        $appIdentifier = $this->batch['appIdentifier'];
        $environment = $this->batch['environment'];
        $isTest = $this->batch['isTest'] ?? false;
        $batchId = $this->batch['batchId'];
        $userId = $this->batch['userId'] ?? $this->batch['user_id'] ?? null;
        $clientIp = $this->batch['_client_ip'] ?? null;
        $receivedAt = Carbon::now('UTC');

        // Perform IP geolocation (city-level)
        $location = $this->geolocateIp($clientIp);

        $eventsToInsert = [];

        foreach ($this->batch['events'] as $event) {
            // Skip if we already have this event (idempotency)
            if (HealthEvent::where('event_id', $event['id'])->exists()) {
                continue;
            }

            $eventsToInsert[] = [
                'event_id' => $event['id'],
                'batch_id' => $batchId,
                'app_identifier' => $appIdentifier,
                'environment' => $environment,
                'is_test' => $isTest,
                'device_id' => $deviceInfo['deviceId'],
                'user_id' => $userId,
                'level' => $event['level'],
                'message' => $event['message'],
                'metadata' => isset($event['metadata']) ? json_encode($event['metadata']) : null,
                'stack_trace' => isset($event['stackTrace']) ? json_encode($event['stackTrace']) : null,
                'device_model' => $deviceInfo['deviceModel'] ?? null,
                'os_version' => $deviceInfo['osVersion'] ?? null,
                'app_version' => $deviceInfo['appVersion'] ?? null,
                'build_number' => $deviceInfo['buildNumber'] ?? null,
                'platform' => $deviceInfo['platform'] ?? 'unknown',
                'city' => $location['city'],
                'region' => $location['region'],
                'country' => $location['country'],
                'event_timestamp' => Carbon::parse($event['timestamp'], 'UTC'),
                'received_at' => $receivedAt,
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ];
        }

        if (!empty($eventsToInsert)) {
            // Bulk insert for performance
            HealthEvent::insert($eventsToInsert);

            Log::info("Processed {$appIdentifier} health batch", [
                'batchId' => $batchId,
                'eventsCount' => count($eventsToInsert),
                'isTest' => $isTest,
            ]);
        }

        // Update aggregated stats (skip test data in stats)
        if (!$isTest) {
            $this->updateStats($appIdentifier, $environment, $eventsToInsert);
        }
    }

    /**
     * Update hourly/daily aggregated stats
     */
    private function updateStats(string $appIdentifier, string $environment, array $events): void
    {
        if (empty($events)) {
            return;
        }

        $date = now()->toDateString();
        $hour = now()->hour;

        $levelCounts = collect($events)->countBy('level')->toArray();

        DB::table('health_stats')->updateOrInsert(
            [
                'app_identifier' => $appIdentifier,
                'environment' => $environment,
                'date' => $date,
                'hour' => $hour,
            ],
            [
                'crash_count' => DB::raw('crash_count + ' . ($levelCounts['crash'] ?? 0)),
                'error_count' => DB::raw('error_count + ' . ($levelCounts['error'] ?? 0)),
                'warning_count' => DB::raw('warning_count + ' . ($levelCounts['warning'] ?? 0)),
                'network_error_count' => DB::raw('network_error_count + ' . ($levelCounts['networkError'] ?? 0)),
                'heartbeat_count' => DB::raw('heartbeat_count + ' . ($levelCounts['heartbeat'] ?? 0)),
                'updated_at' => now(),
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to process health events batch', [
            'batchId' => $this->batch['batchId'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Geolocate IP address to city level
     * Uses ip-api.com (free, 45 req/min) or MaxMind if configured
     */
    private function geolocateIp(?string $ip): array
    {
        $default = [
            'city' => null,
            'region' => null,
            'country' => null,
        ];

        if (!$ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $default;
        }

        // Check cache first (IPs don't change location often)
        $cacheKey = "geo_ip:{$ip}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Option 1: Use MaxMind if installed (torann/geoip package)
            if (function_exists('geoip')) {
                $geo = geoip($ip);
                $location = [
                    'city' => $geo->city ?? null,
                    'region' => $geo->state_name ?? null,
                    'country' => $geo->iso_code ?? null,
                ];
                Cache::put($cacheKey, $location, now()->addDay());
                return $location;
            }

            // Option 2: Use ip-api.com (free tier)
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,city,regionName,countryCode");

            if ($response->successful() && $response->json('status') === 'success') {
                $location = [
                    'city' => $response->json('city'),
                    'region' => $response->json('regionName'),
                    'country' => $response->json('countryCode'),
                ];
                Cache::put($cacheKey, $location, now()->addDay());
                return $location;
            }
        } catch (\Exception $e) {
            // Geolocation is non-critical, don't fail the job
            Log::debug('Geolocation failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return $default;
    }
}
