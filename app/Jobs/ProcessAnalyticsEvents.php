<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAnalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $deviceInfo = $this->payload['deviceInfo'];
        $appIdentifier = $this->payload['appIdentifier'];
        $batchId = $this->payload['batchId'];
        $isTest = $this->payload['isTest'] ?? false;
        $clientIp = $this->payload['_client_ip'] ?? null;
        $userId = $this->payload['userId'] ?? null;

        // Get geolocation from IP using ip-api.com
        $geo = ['country' => null, 'region' => null, 'city' => null, 'latitude' => null, 'longitude' => null];
        if ($clientIp) {
            $geo = $this->getGeoFromIp($clientIp);
        }

        $eventsStored = 0;
        $screenViews = 0;

        // Track sessions we see in this batch
        $sessionsSeen = [];

        foreach ($this->payload['events'] as $event) {
            // Check for duplicate event_id
            if (AnalyticsEvent::where('event_id', $event['id'])->exists()) {
                continue;
            }

            try {
                $eventTimestamp = Carbon::parse($event['timestamp']);
            } catch (\Exception $e) {
                $eventTimestamp = Carbon::now('UTC');
            }

            // Get event name - client sends eventType, fallback to name
            $eventName = $event['eventType'] ?? $event['name'] ?? 'unknown';

            // Get session ID - client sends it per event
            $sessionId = $event['sessionId'] ?? $this->payload['sessionId'] ?? null;

            // Track sessions and screen views
            if ($sessionId && !isset($sessionsSeen[$sessionId])) {
                $sessionsSeen[$sessionId] = ['screenViews' => 0, 'eventCount' => 0];
            }

            // Track screen views for session
            if (in_array($eventName, ['screen_viewed', 'page_viewed', 'screen_view', 'page_view'])) {
                if ($sessionId) {
                    $sessionsSeen[$sessionId]['screenViews']++;
                }
                $screenViews++;
            }

            // Limit properties to 10 keys
            $properties = $event['properties'] ?? null;
            if ($properties && count($properties) > 10) {
                $properties = array_slice($properties, 0, 10, true);
            }

            // Category is optional - derive from event name if not provided
            $category = $event['category'] ?? $this->deriveCategory($eventName);

            AnalyticsEvent::create([
                'event_id' => $event['id'],
                'batch_id' => $batchId,
                'app_identifier' => $appIdentifier,
                'session_id' => $sessionId,
                'anonymous_user_id' => $userId ?? $this->payload['anonymousUserId'] ?? null,
                'event_name' => $eventName,
                'event_category' => $category,
                'screen_name' => $event['screen'] ?? null,
                'element_id' => $event['element'] ?? null,
                'properties' => $properties,
                'duration_ms' => $event['duration'] ?? null,
                'referrer' => $event['referrer'] ?? null,
                'device_id' => $deviceInfo['deviceId'] ?? null, // Null for anonymous analytics
                'device_model' => $deviceInfo['deviceModel'] ?? null,
                'platform' => $deviceInfo['platform'],
                'os_version' => $deviceInfo['osVersion'] ?? null,
                'app_version' => $deviceInfo['appVersion'] ?? null,
                'screen_resolution' => $deviceInfo['screenResolution'] ?? null,
                'language' => $deviceInfo['language'] ?? null,
                'country' => $geo['country'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
                'is_test' => $isTest,
                'event_timestamp' => $eventTimestamp,
                'received_at' => Carbon::now('UTC'),
            ]);

            $eventsStored++;
            if ($sessionId) {
                $sessionsSeen[$sessionId]['eventCount']++;
            }
        }

        // Update sessions
        if ($eventsStored > 0) {
            foreach ($sessionsSeen as $sessionId => $stats) {
                $this->updateSession(
                    $sessionId,
                    $appIdentifier,
                    $deviceInfo,
                    $stats['eventCount'],
                    $stats['screenViews'],
                    $isTest,
                    $geo,
                    $userId
                );
            }
        }

        Log::debug("ProcessAnalyticsEvents: Stored {$eventsStored} events for {$appIdentifier}");
    }

    /**
     * Update or create session record
     */
    protected function updateSession(
        string $sessionId,
        string $appIdentifier,
        array $deviceInfo,
        int $eventCount,
        int $screenViews,
        bool $isTest,
        array $geo,
        ?string $userId = null
    ): void {
        // Key sessions by both session_id AND is_test to keep test/production data separate
        $session = AnalyticsSession::firstOrCreate(
            [
                'session_id' => $sessionId,
                'is_test' => $isTest,
            ],
            [
                'app_identifier' => $appIdentifier,
                'device_id' => $deviceInfo['deviceId'] ?? null, // Null for anonymous analytics
                'anonymous_user_id' => $userId ?? $this->payload['anonymousUserId'] ?? null,
                'started_at' => Carbon::now('UTC'),
                'platform' => $deviceInfo['platform'],
                'app_version' => $deviceInfo['appVersion'] ?? null,
                'country' => $geo['country'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
            ]
        );

        $session->last_activity_at = Carbon::now('UTC');
        $session->event_count += $eventCount;
        $session->screens_viewed += $screenViews;

        if ($session->started_at) {
            $session->duration_seconds = Carbon::now('UTC')->diffInSeconds($session->started_at);
        }

        $session->save();
    }

    /**
     * Derive event category from event name
     */
    protected function deriveCategory(string $eventName): string
    {
        // Navigation events
        if (preg_match('/^(screen_|page_|navigate|route|tab_)/i', $eventName)) {
            return 'navigation';
        }

        // Interaction events
        if (preg_match('/(_clicked|_pressed|_tapped|_selected|_opened|_closed|button_|click_|tap_)/i', $eventName)) {
            return 'interaction';
        }

        // Form events
        if (preg_match('/(_submitted|_changed|_input|_focused|_blur|form_|field_)/i', $eventName)) {
            return 'form';
        }

        // Session/lifecycle events
        if (preg_match('/^(session_|app_|launch|start|end|foreground|background)/i', $eventName)) {
            return 'lifecycle';
        }

        // Feature events
        if (preg_match('/(feature_|enabled|disabled|toggle)/i', $eventName)) {
            return 'feature';
        }

        // Error events
        if (preg_match('/(error|exception|crash|fail)/i', $eventName)) {
            return 'error';
        }

        return 'other';
    }

    /**
     * Get geolocation from IP using ip-api.com (free tier: 45 requests/minute)
     * Returns: country, region, city, latitude, longitude
     * Wrapped in try-catch to never break event processing if geo lookup fails
     */
    protected function getGeoFromIp(?string $ip): array
    {
        $emptyResult = [
            'country' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        // Skip private/local IPs
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return $emptyResult;
        }

        // Skip private IP ranges
        if (str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.') ||
            str_starts_with($ip, '172.17.') ||
            str_starts_with($ip, '172.18.') ||
            str_starts_with($ip, '172.19.') ||
            str_starts_with($ip, '172.2') ||
            str_starts_with($ip, '172.30.') ||
            str_starts_with($ip, '172.31.')) {
            return $emptyResult;
        }

        try {
            // Use ip-api.com free tier (no API key required)
            // Rate limit: 45 requests per minute from an IP address
            // Fields: country, regionName, city, lat, lon
            $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon";

            $context = stream_context_create([
                'http' => [
                    'timeout' => 2, // 2 second timeout - don't slow down event processing
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                Log::debug("Geo lookup failed for IP {$ip}: No response");
                return $emptyResult;
            }

            $data = json_decode($response, true);

            if (!$data || ($data['status'] ?? '') !== 'success') {
                Log::debug("Geo lookup failed for IP {$ip}: " . ($data['message'] ?? 'Unknown error'));
                return $emptyResult;
            }

            return [
                'country' => $data['country'] ?? null,
                'region' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => isset($data['lat']) ? (float) $data['lat'] : null,
                'longitude' => isset($data['lon']) ? (float) $data['lon'] : null,
            ];

        } catch (\Throwable $e) {
            // Log but never fail - geo data is nice to have, not critical
            Log::debug("Geo lookup exception for IP {$ip}: " . $e->getMessage());
            return $emptyResult;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAnalyticsEvents failed', [
            'batch_id' => $this->payload['batchId'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
