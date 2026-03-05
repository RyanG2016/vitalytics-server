<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessHealthEvents;
use App\Jobs\CheckHealthAlerts;
use App\Models\HealthEvent;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\DeviceHeartbeat;
use App\Models\MaintenanceNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class HealthEventsController extends Controller
{
    /**
     * Receive health events from client SDKs
     *
     * POST /api/v1/health/events
     */
    public function store(Request $request): JsonResponse
    {
        // Validate API key
        $apiKey = $request->header('X-API-Key');
        $appIdentifier = $request->header('X-App-Identifier');

        if (!$this->validateApiKey($apiKey, $appIdentifier)) {
            Log::warning('Vitalytics: Invalid API key attempt', [
                'app' => $appIdentifier,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if this is a write-only key (not a read key)
        if (!$this->isWriteKey($apiKey, $appIdentifier)) {
            return response()->json(['error' => 'Invalid key type'], 403);
        }

        // Rate limiting: 100 requests per minute per device
        $deviceId = $request->input('deviceInfo.deviceId', $request->ip());
        $rateLimitKey = "vitalytics_events:{$appIdentifier}:{$deviceId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 100)) {
            Log::warning('Vitalytics: Rate limit exceeded', [
                'app' => $appIdentifier,
                'device' => $deviceId,
            ]);
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Anomaly detection: flag if single key has too many unique devices
        $this->trackDeviceAnomaly($appIdentifier, $deviceId);

        // Validate payload structure
        $validated = $request->validate([
            'batchId' => 'required|string',
            'deviceInfo' => 'required|array',
            'deviceInfo.deviceId' => 'required|string',
            'deviceInfo.deviceModel' => 'nullable|string',
            'deviceInfo.osVersion' => 'nullable|string',
            'deviceInfo.appVersion' => 'nullable|string',
            'deviceInfo.buildNumber' => 'nullable|string',
            'deviceInfo.platform' => 'nullable|string',
            'appIdentifier' => 'required|string',
            'environment' => 'required|string',
            'userId' => 'nullable|string',
            'user_id' => 'nullable|string',
            'events' => 'required|array',
            'events.*.id' => 'required|string',
            'events.*.timestamp' => 'required|string',
            'events.*.level' => 'required|string',
            'events.*.message' => 'required|string',
            'events.*.metadata' => 'nullable|array',
            'events.*.stackTrace' => 'nullable|array',
            'sentAt' => 'required|string',
            'isTest' => 'nullable|boolean',
        ]);

        // Queue for async processing (include IP for geolocation)
        $validated['_client_ip'] = $request->ip();

        ProcessHealthEvents::dispatch($validated);

        // Record heartbeats for monitoring
        $hasHeartbeat = collect($validated['events'])
            ->contains(fn($e) => $e['level'] === 'heartbeat');

        if ($hasHeartbeat) {
            $app = App::where('identifier', $appIdentifier)->first();
            if ($app && $app->product_id) {
                DeviceHeartbeat::recordHeartbeat(
                    $app->product_id,
                    $appIdentifier,
                    $validated['deviceInfo']['deviceId'],
                    [
                        'device_model' => $validated['deviceInfo']['deviceModel'] ?? null,
                        'os_version' => $validated['deviceInfo']['osVersion'] ?? null,
                        'app_version' => $validated['deviceInfo']['appVersion'] ?? null,
                    ]
                );
            }
        }

        // Check for critical events that need immediate alerting
        $criticalLevels = ['crash', 'error'];
        $hasCritical = collect($validated['events'])
            ->contains(fn($e) => in_array($e['level'], $criticalLevels));

        if ($hasCritical) {
            CheckHealthAlerts::dispatch($appIdentifier, $validated['events']);
        }

        // Build response with optional config metadata
        $response = [
            'success' => true,
            'batchId' => $validated['batchId'],
            'eventsReceived' => count($validated['events']),
        ];

        // Include config metadata if any configs exist for this app
        $configs = AppConfig::forApp($appIdentifier)->active()->get();
        if ($configs->isNotEmpty()) {
            $configMeta = [];
            foreach ($configs as $config) {
                $configMeta[$config->config_key] = [
                    'version' => $config->current_version,
                    'hash' => $config->getCurrentHash(),
                ];
            }
            $response['configs'] = $configMeta;
        }

        // Include active maintenance notifications for this app
        $isTest = $validated['isTest'] ?? false;
        $maintenanceNotifications = MaintenanceNotification::getActiveForApp($appIdentifier, $isTest);
        if ($maintenanceNotifications->isNotEmpty()) {
            $response['maintenance'] = $maintenanceNotifications->map(fn($n) => $n->toApiArray())->values()->all();
        }

        return response()->json($response);
    }

    /**
     * Get health status overview for an app
     *
     * GET /api/v1/health/status/{appIdentifier}
     */
    public function status(Request $request, string $appIdentifier): JsonResponse
    {
        $hours = $request->input('hours', 24);
        $since = now()->subHours($hours);

        // Get event counts by level
        $counts = HealthEvent::where('app_identifier', $appIdentifier)
            ->where('event_timestamp', '>=', $since)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        // Get unique devices
        $activeDevices = HealthEvent::where('app_identifier', $appIdentifier)
            ->where('event_timestamp', '>=', $since)
            ->distinct('device_id')
            ->count('device_id');

        // Recent crashes
        $recentCrashes = HealthEvent::where('app_identifier', $appIdentifier)
            ->where('level', 'crash')
            ->where('event_timestamp', '>=', $since)
            ->orderBy('event_timestamp', 'desc')
            ->limit(10)
            ->get(['id', 'message', 'device_id', 'app_version', 'event_timestamp']);

        // Recent errors
        $recentErrors = HealthEvent::where('app_identifier', $appIdentifier)
            ->where('level', 'error')
            ->where('event_timestamp', '>=', $since)
            ->orderBy('event_timestamp', 'desc')
            ->limit(10)
            ->get(['id', 'message', 'metadata', 'event_timestamp']);

        // Health score (simple heuristic)
        $crashCount = $counts['crash'] ?? 0;
        $errorCount = $counts['error'] ?? 0;
        $healthScore = max(0, 100 - ($crashCount * 10) - ($errorCount * 2));

        return response()->json([
            'appIdentifier' => $appIdentifier,
            'periodHours' => $hours,
            'healthScore' => $healthScore,
            'status' => $healthScore >= 80 ? 'healthy' : ($healthScore >= 50 ? 'degraded' : 'critical'),
            'counts' => [
                'crashes' => $counts['crash'] ?? 0,
                'errors' => $counts['error'] ?? 0,
                'warnings' => $counts['warning'] ?? 0,
                'networkErrors' => $counts['networkError'] ?? 0,
                'heartbeats' => $counts['heartbeat'] ?? 0,
            ],
            'activeDevices' => $activeDevices,
            'recentCrashes' => $recentCrashes,
            'recentErrors' => $recentErrors,
        ]);
    }

    /**
     * Get detailed events list
     *
     * GET /api/v1/health/events/{appIdentifier}
     */
    public function index(Request $request, string $appIdentifier): JsonResponse
    {
        $query = HealthEvent::where('app_identifier', $appIdentifier);

        // Filters
        if ($level = $request->input('level')) {
            $query->where('level', $level);
        }

        if ($deviceId = $request->input('device_id')) {
            $query->where('device_id', $deviceId);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($since = $request->input('since')) {
            $query->where('event_timestamp', '>=', $since);
        }

        if ($until = $request->input('until')) {
            $query->where('event_timestamp', '<=', $until);
        }

        if ($search = $request->input('search')) {
            $query->where('message', 'like', "%{$search}%");
        }

        $events = $query
            ->orderBy('event_timestamp', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($events);
    }

    /**
     * Get single event details
     *
     * GET /api/v1/health/events/{appIdentifier}/{eventId}
     */
    public function show(string $appIdentifier, string $eventId): JsonResponse
    {
        $event = HealthEvent::where('app_identifier', $appIdentifier)
            ->where('event_id', $eventId)
            ->firstOrFail();

        return response()->json($event);
    }

    /**
     * Validate API key against database and configured keys
     */
    private function validateApiKey(?string $apiKey, ?string $appIdentifier): bool
    {
        if (!$apiKey || !$appIdentifier) {
            return false;
        }

        // Check database first (new method)
        $app = App::findByApiKey($appIdentifier, $apiKey);
        if ($app) {
            return true;
        }

        // Fallback to config for backward compatibility
        $writeKeys = config('vitalytics.api_keys', []);
        if (isset($writeKeys[$appIdentifier]) && $writeKeys[$appIdentifier] === $apiKey) {
            return true;
        }

        // Check read keys (for dashboard access)
        $readKeys = config('vitalytics.read_keys', []);
        if (isset($readKeys[$appIdentifier]) && $readKeys[$appIdentifier] === $apiKey) {
            return true;
        }

        return false;
    }

    /**
     * Check if the key is a write-only key (for receiving events)
     */
    private function isWriteKey(?string $apiKey, ?string $appIdentifier): bool
    {
        // Check database first
        $app = App::findByApiKey($appIdentifier, $apiKey);
        if ($app) {
            return true;
        }

        // Fallback to config
        $writeKeys = config('vitalytics.api_keys', []);
        return isset($writeKeys[$appIdentifier]) && $writeKeys[$appIdentifier] === $apiKey;
    }

    /**
     * Track unique devices per app to detect anomalies
     * Flags if a single app suddenly has a spike in unique devices (possible key leak)
     */
    private function trackDeviceAnomaly(string $appIdentifier, string $deviceId): void
    {
        $cacheKey = "vitalytics_devices:{$appIdentifier}";
        $windowKey = "vitalytics_device_window:{$appIdentifier}";

        // Get current hour's device set
        $devices = Cache::get($cacheKey, []);
        $devices[$deviceId] = true;

        // Store for 1 hour
        Cache::put($cacheKey, $devices, now()->addHour());

        // Check threshold (configurable, default 1000 unique devices per hour)
        $threshold = config('vitalytics.anomaly_threshold', 1000);

        if (count($devices) > $threshold) {
            // Only alert once per hour
            if (!Cache::has($windowKey)) {
                Cache::put($windowKey, true, now()->addHour());

                Log::warning('Vitalytics: Anomaly detected - high unique device count', [
                    'app' => $appIdentifier,
                    'device_count' => count($devices),
                    'threshold' => $threshold,
                ]);

                // Could trigger Slack alert here if configured
            }
        }
    }
}
