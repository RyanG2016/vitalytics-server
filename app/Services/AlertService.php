<?php

namespace App\Services;

use App\Models\Product;
use App\Models\App;
use App\Models\ProductAlertSetting;
use App\Models\AlertSubscriber;
use App\Models\AlertHistory;
use App\Models\DeviceHeartbeat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlertService
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Process an event and send alerts if needed
     */
    public function processEvent(array $event, string $appIdentifier): void
    {
        $app = App::where('identifier', $appIdentifier)->with('product')->first();
        
        if (!$app || !$app->product) {
            Log::warning('AlertService: Unknown app identifier', ['app' => $appIdentifier]);
            return;
        }

        $product = $app->product;
        $settings = ProductAlertSetting::forProduct($product->id);
        $level = $event['level'] ?? 'info';

        // Skip test data if not configured to alert on it
        $isTestData = $event['is_test'] ?? false;
        if ($isTestData && !$settings->alert_on_test_data) {
            Log::debug('AlertService: Skipping test data event');
            return;
        }

        // Skip heartbeats and info events
        if (in_array($level, ['heartbeat', 'info'])) {
            return;
        }

        // Check business hours if configured
        if (!$settings->isWithinBusinessHours()) {
            Log::debug('AlertService: Outside business hours, skipping alert');
            return;
        }

        // Determine if this is a critical alert
        $isCritical = $level === 'crash';

        if ($isCritical) {
            $this->processCriticalAlert($event, $app, $product, $settings);
        } else {
            $this->processNonCriticalAlert($event, $app, $product, $settings);
        }
    }

    /**
     * Process critical alert (crash) - Teams + Email + Push
     */
    protected function processCriticalAlert(
        array $event,
        App $app,
        Product $product,
        ProductAlertSetting $settings
    ): void {
        $message = $event['message'] ?? 'Unknown crash';

        // Check throttling for Teams
        if ($settings->teams_enabled && $settings->teams_webhook_url) {
            $teamsHistory = AlertHistory::findOrCreateForError(
                $product->id,
                $app->identifier,
                'crash',
                $message,
                'teams'
            );

            if ($teamsHistory->shouldAlert($settings->critical_cooldown_minutes)) {
                $this->sendTeamsAlert($event, $app, $product, $settings, $teamsHistory);
                $teamsHistory->markAlerted();
            }
        }

        // Check throttling for Email
        if ($settings->email_enabled) {
            $emailHistory = AlertHistory::findOrCreateForError(
                $product->id,
                $app->identifier,
                'crash',
                $message,
                'email'
            );

            if ($emailHistory->shouldAlert($settings->critical_cooldown_minutes)) {
                $this->sendEmailAlert($event, $app, $product, $settings, true, $emailHistory);
                $emailHistory->markAlerted();
            }
        }

        // Send push notifications (separate throttling)
        if ($settings->push_enabled) {
            $pushHistory = AlertHistory::findOrCreateForError(
                $product->id,
                $app->identifier,
                'crash',
                $message,
                'push'
            );

            if ($pushHistory->shouldAlert($settings->critical_cooldown_minutes)) {
                $this->sendPushAlert($event, $app, $product, true);
                $pushHistory->markAlerted();
            }
        }
    }

    /**
     * Process non-critical alert (error, warning, network) - Email only with threshold
     */
    protected function processNonCriticalAlert(
        array $event,
        App $app,
        Product $product,
        ProductAlertSetting $settings
    ): void {
        if (!$settings->email_enabled) {
            return;
        }

        $level = $event['level'] ?? 'error';
        $message = $event['message'] ?? 'Unknown error';

        // Find or create history entry
        $history = AlertHistory::findOrCreateForError(
            $product->id,
            $app->identifier,
            $level,
            $message,
            'email'
        );

        // Check if we've hit the threshold
        if ($history->occurrence_count < $settings->noncritical_threshold) {
            return;
        }

        // Check cooldown
        $cooldownMinutes = $settings->noncritical_cooldown_hours * 60;
        if (!$history->shouldAlert($cooldownMinutes)) {
            return;
        }

        // Send grouped alert
        $this->sendEmailAlert($event, $app, $product, $settings, false, $history);
        $history->markAlerted();
    }

    /**
     * Send Teams alert
     */
    protected function sendTeamsAlert(
        array $event,
        App $app,
        Product $product,
        ProductAlertSetting $settings,
        AlertHistory $history
    ): void {
        $webhookUrl = $settings->teams_webhook_url;
        
        if (!$webhookUrl) {
            return;
        }

        $level = $event['level'] ?? 'error';
        $message = $event['message'] ?? 'Unknown error';
        $deviceInfo = $this->formatDeviceInfo($event);
        $time = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');

        $title = $level === 'crash' ? '🚨 CRASH DETECTED' : '⚠️ ERROR ALERT';
        $style = $level === 'crash' ? 'attention' : 'warning';

        $occurrenceText = $history->occurrence_count > 1 
            ? " ({$history->occurrence_count} occurrences)"
            : "";

        $payload = [
            'type' => 'message',
            'attachments' => [[
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    'type' => 'AdaptiveCard',
                    'version' => '1.4',
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'body' => [
                        [
                            'type' => 'Container',
                            'style' => $style,
                            'items' => [[
                                'type' => 'TextBlock',
                                'text' => $title,
                                'weight' => 'bolder',
                                'size' => 'large',
                                'color' => $style,
                            ]],
                        ],
                        [
                            'type' => 'FactSet',
                            'facts' => [
                                ['title' => 'Product', 'value' => $product->name],
                                ['title' => 'App', 'value' => $app->name . " ({$app->identifier})"],
                                ['title' => 'Message', 'value' => $this->truncate($message, 200) . $occurrenceText],
                                ['title' => 'Device', 'value' => $deviceInfo],
                                ['title' => 'Time', 'value' => $time],
                            ],
                        ],
                        [
                            'type' => 'ActionSet',
                            'actions' => [[
                                'type' => 'Action.OpenUrl',
                                'title' => 'View in Dashboard',
                                'url' => config('app.url') . '/events?level=' . $level . '&app=' . $app->identifier,
                            ]],
                        ],
                    ],
                ],
            ]],
        ];

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);
            
            if (!$response->successful()) {
                Log::error('AlertService: Teams webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AlertService: Teams webhook exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email alert to subscribers
     */
    protected function sendEmailAlert(
        array $event,
        App $app,
        Product $product,
        ProductAlertSetting $settings,
        bool $isCritical,
        AlertHistory $history
    ): void {
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->when($isCritical, fn($q) => $q->critical())
            ->when(!$isCritical, fn($q) => $q->noncritical())
            ->with('user')
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        $level = $event['level'] ?? 'error';
        $message = $event['message'] ?? 'Unknown error';
        $deviceInfo = $this->formatDeviceInfo($event);
        $time = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');

        $subject = $isCritical
            ? "[CRITICAL] {$product->name} - Crash detected"
            : "[Alert] {$product->name} - {$level} threshold reached";

        foreach ($subscribers as $subscriber) {
            $email = $subscriber->email_address;
            
            if (!$email) {
                continue;
            }

            try {
                Mail::raw(
                    $this->buildEmailBody($event, $app, $product, $deviceInfo, $time, $history, $isCritical),
                    function ($mail) use ($email, $subject, $subscriber) {
                        $mail->to($email, $subscriber->display_name)
                            ->subject($subject);
                    }
                );
            } catch (\Exception $e) {
                Log::error('AlertService: Email send failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build email body
     */
    protected function buildEmailBody(
        array $event,
        App $app,
        Product $product,
        string $deviceInfo,
        string $time,
        AlertHistory $history,
        bool $isCritical
    ): string {
        $level = $event['level'] ?? 'error';
        $message = $event['message'] ?? 'Unknown error';

        $body = $isCritical
            ? "CRASH DETECTED\n\n"
            : "ALERT - {$level} threshold reached\n\n";

        $body .= "Product: {$product->name}\n";
        $body .= "App: {$app->name} ({$app->identifier})\n";
        $body .= "Level: {$level}\n";
        $body .= "Occurrences: {$history->occurrence_count}\n";
        $body .= "Message: {$message}\n";
        $body .= "Device: {$deviceInfo}\n";
        $body .= "Time: {$time}\n";
        $body .= "\n";
        $body .= "View in Dashboard: " . config('app.url') . "/events?level={$level}&app={$app->identifier}\n";

        if (isset($event['stackTrace']) && is_array($event['stackTrace'])) {
            $body .= "\nStack Trace:\n";
            $body .= implode("\n", array_slice($event['stackTrace'], 0, 10));
        }

        return $body;
    }

    /**
     * Format device info from event
     */
    protected function formatDeviceInfo(array $event): string
    {
        $parts = [];

        if (isset($event['deviceModel'])) {
            $parts[] = $event['deviceModel'];
        }

        if (isset($event['osVersion'])) {
            $parts[] = $event['osVersion'];
        }

        if (isset($event['appVersion'])) {
            $parts[] = "v{$event['appVersion']}";
        }

        return implode(' - ', $parts) ?: 'Unknown device';
    }

    /**
     * Truncate a string
     */
    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }

    /**
     * Send reminder alerts for uncleared critical errors
     */
    public function sendReminders(): void
    {
        $settings = ProductAlertSetting::where('teams_enabled', true)
            ->orWhere('email_enabled', true)
            ->get();

        foreach ($settings as $setting) {
            $histories = AlertHistory::where('product_id', $setting->product_id)
                ->level('crash')
                ->active()
                ->whereNotNull('last_alerted_at')
                ->get();

            foreach ($histories as $history) {
                if ($history->shouldSendReminder($setting->critical_reminder_hours)) {
                    $this->sendReminderAlert($history, $setting);
                }
            }
        }
    }

    /**
     * Send a reminder alert for an uncleared critical error
     */
    protected function sendReminderAlert(AlertHistory $history, ProductAlertSetting $settings): void
    {
        $product = Product::find($history->product_id);
        $app = App::where('identifier', $history->app_identifier)->first();

        if (!$product || !$app) {
            return;
        }

        $time = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');
        $duration = $history->first_occurrence_at->diffForHumans(null, true);

        // Send Teams reminder
        if ($settings->teams_enabled && $settings->teams_webhook_url) {
            $payload = [
                'type' => 'message',
                'attachments' => [[
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'content' => [
                        'type' => 'AdaptiveCard',
                        'version' => '1.4',
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'body' => [
                            [
                                'type' => 'Container',
                                'style' => 'attention',
                                'items' => [[
                                    'type' => 'TextBlock',
                                    'text' => '🔔 REMINDER: Unresolved Crash',
                                    'weight' => 'bolder',
                                    'size' => 'large',
                                    'color' => 'attention',
                                ]],
                            ],
                            [
                                'type' => 'FactSet',
                                'facts' => [
                                    ['title' => 'Product', 'value' => $product->name],
                                    ['title' => 'App', 'value' => $app->name],
                                    ['title' => 'Duration', 'value' => "Unresolved for {$duration}"],
                                    ['title' => 'Occurrences', 'value' => (string) $history->occurrence_count],
                                    ['title' => 'Time', 'value' => $time],
                                ],
                            ],
                        ],
                    ],
                ]],
            ];

            try {
                Http::timeout(10)->post($settings->teams_webhook_url, $payload);
            } catch (\Exception $e) {
                Log::error('AlertService: Teams reminder failed', ['error' => $e->getMessage()]);
            }
        }

        $history->markAlerted();
    }

    /**
     * Send heartbeat missed alert
     */
    public function sendHeartbeatAlert(DeviceHeartbeat $device, ProductAlertSetting $settings): void
    {
        $product = $device->product;
        $app = App::where('identifier', $device->app_identifier)->first();

        if (!$product) {
            return;
        }

        $time = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');
        $lastSeen = $device->last_heartbeat_at->setTimezone($settings->timezone)->format('M j, Y g:i A T');
        $missingFor = $device->last_heartbeat_at->diffForHumans(null, true);

        // Send Teams alert if enabled
        if ($settings->teams_enabled && $settings->teams_webhook_url) {
            $this->sendHeartbeatTeamsAlert($device, $product, $app, $settings, $time, $lastSeen, $missingFor);
        }

        // Send email alert if enabled
        if ($settings->email_enabled) {
            $this->sendHeartbeatEmailAlert($device, $product, $app, $settings, $time, $lastSeen, $missingFor);
        }

        // Send push notification if enabled
        if ($settings->push_enabled) {
            $this->sendHeartbeatPushAlert($device, $product, $missingFor);
        }
    }

    /**
     * Send heartbeat Teams alert
     */
    protected function sendHeartbeatTeamsAlert(
        DeviceHeartbeat $device,
        Product $product,
        ?App $app,
        ProductAlertSetting $settings,
        string $time,
        string $lastSeen,
        string $missingFor
    ): void {
        $appName = $app ? $app->name : $device->app_identifier;

        $payload = [
            'type' => 'message',
            'attachments' => [[
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    'type' => 'AdaptiveCard',
                    'version' => '1.4',
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'body' => [
                        [
                            'type' => 'Container',
                            'style' => 'attention',
                            'items' => [[
                                'type' => 'TextBlock',
                                'text' => '💔 HEARTBEAT MISSING',
                                'weight' => 'bolder',
                                'size' => 'large',
                                'color' => 'attention',
                            ]],
                        ],
                        [
                            'type' => 'FactSet',
                            'facts' => [
                                ['title' => 'Product', 'value' => $product->name],
                                ['title' => 'App', 'value' => $appName],
                                ['title' => 'Device', 'value' => $device->display_name],
                                ['title' => 'Device ID', 'value' => $device->device_id],
                                ['title' => 'Last Seen', 'value' => $lastSeen],
                                ['title' => 'Missing For', 'value' => $missingFor],
                                ['title' => 'Alert Time', 'value' => $time],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        try {
            Http::timeout(10)->post($settings->teams_webhook_url, $payload);
        } catch (\Exception $e) {
            Log::error('AlertService: Teams heartbeat alert failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send heartbeat email alert
     */
    protected function sendHeartbeatEmailAlert(
        DeviceHeartbeat $device,
        Product $product,
        ?App $app,
        ProductAlertSetting $settings,
        string $time,
        string $lastSeen,
        string $missingFor
    ): void {
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->critical()
            ->with('user')
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        $appName = $app ? $app->name : $device->app_identifier;
        $subject = "[CRITICAL] {$product->name} - Heartbeat missing from {$device->display_name}";

        $body = "HEARTBEAT MISSING\n\n";
        $body .= "Product: {$product->name}\n";
        $body .= "App: {$appName}\n";
        $body .= "Device: {$device->display_name}\n";
        $body .= "Device ID: {$device->device_id}\n";
        $body .= "Last Seen: {$lastSeen}\n";
        $body .= "Missing For: {$missingFor}\n";
        $body .= "Alert Time: {$time}\n";

        foreach ($subscribers as $subscriber) {
            $email = $subscriber->email_address;

            if (!$email) {
                continue;
            }

            try {
                Mail::raw($body, function ($mail) use ($email, $subject, $subscriber) {
                    $mail->to($email, $subscriber->display_name)
                        ->subject($subject);
                });
            } catch (\Exception $e) {
                Log::error('AlertService: Heartbeat email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send push notification alert to subscribers with registered devices
     */
    protected function sendPushAlert(
        array $event,
        App $app,
        Product $product,
        bool $isCritical
    ): void {
        // Get subscribers with user accounts who receive critical alerts
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->critical()
            ->whereNotNull('user_id')
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        $level = $event['level'] ?? 'error';
        $message = $event['message'] ?? 'Unknown error';

        // Build notification content
        $title = $level === 'crash'
            ? "🚨 {$product->name} - Crash"
            : "⚠️ {$product->name} - {$level}";

        $body = $this->truncate($message, 100);

        // Build deep link - include event_id if available for direct navigation
        $eventId = $event['id'] ?? null;
        $deepLink = $eventId
            ? "vitalytics://health-event/{$eventId}"
            : "vitalytics://events?level={$level}&app={$app->identifier}";

        $data = [
            'type' => 'health_alert',
            'product' => $product->name,
            'product_id' => $product->id,
            'app' => $app->name,
            'app_identifier' => $app->identifier,
            'level' => $level,
            'event_id' => $eventId,
            'deep_link' => $deepLink,
            'priority' => 'critical',
        ];

        foreach ($subscribers as $subscriber) {
            if ($subscriber->user_id) {
                try {
                    $this->pushService->sendCriticalAlert(
                        $subscriber->user_id,
                        $title,
                        $body,
                        $data
                    );
                } catch (\Exception $e) {
                    Log::error('AlertService: Push notification failed', [
                        'user_id' => $subscriber->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Send push notification for heartbeat alert
     */
    public function sendHeartbeatPushAlert(
        DeviceHeartbeat $device,
        Product $product,
        string $missingFor
    ): void {
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->critical()
            ->whereNotNull('user_id')
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        $title = "💔 {$product->name} - Heartbeat Missing";
        $body = "{$device->display_name} - offline for {$missingFor}";

        $data = [
            'type' => 'heartbeat_alert',
            'product' => $product->name,
            'product_id' => $product->id,
            'device_name' => $device->display_name,
            'device_id' => $device->device_id,
            'deep_link' => 'vitalytics://devices',
            'priority' => 'critical',
        ];

        foreach ($subscribers as $subscriber) {
            if ($subscriber->user_id) {
                try {
                    $this->pushService->sendCriticalAlert(
                        $subscriber->user_id,
                        $title,
                        $body,
                        $data
                    );
                } catch (\Exception $e) {
                    Log::error('AlertService: Heartbeat push notification failed', [
                        'user_id' => $subscriber->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
