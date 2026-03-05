<?php

namespace App\Services;

use App\Models\Product;
use App\Models\HealthEvent;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\ProductAlertSetting;
use App\Models\AlertSubscriber;
use App\Models\AiSummary;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiAnalysisService
{
    protected string $apiKey;
    protected string $model = 'claude-sonnet-4-20250514';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
    }

    /**
     * Generate daily analysis for a product
     */
    public function generateDailyAnalysis(Product $product): ?string
    {
        $settings = ProductAlertSetting::forProduct($product->id);
        
        if (!$settings->ai_analysis_enabled) {
            return null;
        }

        // Get events from last 24 hours (excluding heartbeats)
        $since = Carbon::now()->subHours(24);
        $events = $this->getEventsData($product, $since);

        if ($events['total'] === 0) {
            Log::info("AiAnalysis: No events for {$product->name}, skipping");
            return null;
        }

        // Generate analysis with Claude
        $analysis = $this->callClaudeApi($product, $events, $settings);

        if (!$analysis) {
            return null;
        }

        // Store the summary in the database
        $this->storeSummary($product, 'health', $analysis, $events);

        // Send email to subscribers
        $this->sendAnalysisEmail($product, $analysis, $events, $settings);

        return $analysis;
    }

    /**
     * Get event data for analysis
     */
    protected function getEventsData(Product $product, Carbon $since): array
    {
        // Get all apps for this product
        $appIdentifiers = $product->apps()->pluck('identifier')->toArray();

        if (empty($appIdentifiers)) {
            return ['total' => 0];
        }

        // Query events (excluding heartbeats)
        $query = HealthEvent::whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('level', '!=', 'heartbeat')
            ->where('is_test', false)
            ->whereNull('dismissed_at');

        // Count by level
        $levelCounts = (clone $query)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $total = array_sum($levelCounts);

        if ($total === 0) {
            return ['total' => 0];
        }

        // Top error messages
        $topErrors = (clone $query)
            ->whereIn('level', ['crash', 'error'])
            ->selectRaw('message, level, COUNT(*) as count')
            ->groupBy('message', 'level')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        // Events by app
        $byApp = (clone $query)
            ->selectRaw('app_identifier, level, COUNT(*) as count')
            ->groupBy('app_identifier', 'level')
            ->get()
            ->groupBy('app_identifier')
            ->toArray();

        // Events by hour (for pattern detection)
        $byHour = (clone $query)
            ->selectRaw('HOUR(event_timestamp) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Unique devices affected
        $devicesAffected = (clone $query)
            ->whereIn('level', ['crash', 'error'])
            ->distinct('device_id')
            ->count('device_id');

        // Recent crashes with details
        $recentCrashes = HealthEvent::whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('level', 'crash')
            ->orderByDesc('event_timestamp')
            ->limit(5)
            ->get(['message', 'app_identifier', 'device_model', 'os_version', 'app_version', 'stack_trace', 'event_timestamp'])
            ->toArray();

        // Get previous day data for comparison
        $previousDayStart = $since->copy()->subHours(24);
        $previousTotal = HealthEvent::whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $previousDayStart)
            ->where('event_timestamp', '<', $since)
            ->where('level', '!=', 'heartbeat')
            ->count();

        return [
            'total' => $total,
            'by_level' => $levelCounts,
            'top_errors' => $topErrors,
            'by_app' => $byApp,
            'by_hour' => $byHour,
            'devices_affected' => $devicesAffected,
            'recent_crashes' => $recentCrashes,
            'previous_day_total' => $previousTotal,
            'period' => [
                'start' => $since->toIso8601String(),
                'end' => Carbon::now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Call Claude API for analysis
     */
    protected function callClaudeApi(Product $product, array $events, ProductAlertSetting $settings): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('AiAnalysis: No Anthropic API key configured');
            return null;
        }

        $prompt = $this->buildPrompt($product, $events, $settings);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => 1500,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('AiAnalysis: Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['content'][0]['text'] ?? null;

        } catch (\Exception $e) {
            Log::error('AiAnalysis: Claude API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build the prompt for Claude
     */
    protected function buildPrompt(Product $product, array $events, ProductAlertSetting $settings): string
    {
        $timezone = $settings->timezone;
        $now = Carbon::now()->setTimezone($timezone)->format('M j, Y');

        // Format the data
        $dataJson = json_encode($events, JSON_PRETTY_PRINT);

        // Format crashes for readability
        $crashDetails = '';

        // Pre-compute level counts for heredoc
        $crashes = $events['by_level']['crash'] ?? 0;
        $errors = $events['by_level']['error'] ?? 0;
        $warnings = $events['by_level']['warning'] ?? 0;
        $networkErrors = $events['by_level']['networkError'] ?? 0;
        $topErrorsFormatted = $this->formatTopErrors($events['top_errors']);
        $hourlyFormatted = $this->formatHourlyData($events['by_hour']);

        if (!empty($events['recent_crashes'])) {
            $crashDetails = "\n\nRecent Crash Details:\n";
            foreach ($events['recent_crashes'] as $i => $crash) {
                $crashDetails .= sprintf(
                    "%d. %s\n   App: %s | Device: %s | OS: %s | Version: %s\n",
                    $i + 1,
                    $this->truncate($crash['message'] ?? 'Unknown', 150),
                    $crash['app_identifier'] ?? 'Unknown',
                    $crash['device_model'] ?? 'Unknown',
                    $crash['os_version'] ?? 'Unknown',
                    $crash['app_version'] ?? 'Unknown'
                );
                if (!empty($crash['stack_trace'])) {
                    $stackLines = is_array($crash['stack_trace']) 
                        ? array_slice($crash['stack_trace'], 0, 3) 
                        : array_slice(explode("\n", $crash['stack_trace']), 0, 3);
                    $crashDetails .= "   Stack: " . implode(' -> ', $stackLines) . "\n";
                }
            }
        }

        return <<<PROMPT
You are analyzing health monitoring data for "{$product->name}" application. Generate a concise daily health report for {$now}.

EVENT DATA (Last 24 hours):
- Total events: {$events['total']}
- Previous day total: {$events['previous_day_total']}
- Devices affected by errors/crashes: {$events['devices_affected']}

By Level:
- Crashes: {$crashes}
- Errors: {$errors}
- Warnings: {$warnings}
- Network Errors: {$networkErrors}

Top Error Messages:
{$topErrorsFormatted}

Hourly Distribution (24h format):
{$hourlyFormatted}
{$crashDetails}

Please provide a report with:

1. **Health Score** (1-10) with a brief assessment (Excellent/Good/Fair/Poor/Critical)

2. **Executive Summary** (2-3 sentences summarizing the day)

3. **Key Issues** (bullet points of the most important problems to address)

4. **Patterns Detected** (any trends, time-based patterns, or correlations noticed)

5. **Recommendations** (actionable next steps, prioritized)

6. **Comparison** (brief note on how today compares to yesterday)

Keep the report concise but actionable. Use plain text formatting suitable for email.
PROMPT;
    }

    /**
     * Format top errors for prompt
     */
    protected function formatTopErrors(array $errors): string
    {
        if (empty($errors)) {
            return "None recorded";
        }

        $lines = [];
        foreach ($errors as $error) {
            $lines[] = sprintf(
                "- [%s] %s (%d occurrences)",
                strtoupper($error['level']),
                $this->truncate($error['message'], 100),
                $error['count']
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Format hourly data for prompt
     */
    protected function formatHourlyData(array $byHour): string
    {
        if (empty($byHour)) {
            return "No hourly data";
        }

        $lines = [];
        foreach ($byHour as $hour => $count) {
            $lines[] = sprintf("%02d:00 - %d events", $hour, $count);
        }
        return implode(", ", $lines);
    }

    /**
     * Send analysis email to subscribers
     */
    protected function sendAnalysisEmail(
        Product $product,
        string $analysis,
        array $events,
        ProductAlertSetting $settings
    ): void {
        // Get subscribers who receive critical alerts (using same list as critical alerts)
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->critical()
            ->with('user')
            ->get();

        if ($subscribers->isEmpty()) {
            Log::info("AiAnalysis: No subscribers for {$product->name}");
            return;
        }

        $date = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y');
        $subject = "[Daily Report] {$product->name} - Health Analysis for {$date}";

        $body = $this->buildEmailBody($product, $analysis, $events, $settings);

        foreach ($subscribers as $subscriber) {
            $email = $subscriber->email_address;

            if (!$email) {
                continue;
            }

            try {
                Mail::html($body, function ($mail) use ($email, $subject, $subscriber) {
                    $mail->to($email, $subscriber->display_name)
                        ->subject($subject);
                });

                Log::info("AiAnalysis: Sent report to {$email} for {$product->name}");
            } catch (\Exception $e) {
                Log::error('AiAnalysis: Email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build the email body as HTML
     */
    protected function buildEmailBody(
        Product $product,
        string $analysis,
        array $events,
        ProductAlertSetting $settings
    ): string {
        $date = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');
        $crashes = $events['by_level']['crash'] ?? 0;
        $errors = $events['by_level']['error'] ?? 0;
        $warnings = $events['by_level']['warning'] ?? 0;
        $dashboardUrl = config('app.url') . '/events';
        $productName = htmlspecialchars($product->name);

        // Convert markdown-style analysis to HTML
        $analysisHtml = $this->markdownToHtml($analysis);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Health Report - {$productName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Daily Health Report</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">{$productName}</p>
                            <p style="color: rgba(255,255,255,0.7); margin: 5px 0 0 0; font-size: 13px;">Generated: {$date}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #fef2f2; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #dc2626;">{$crashes}</div>
                                        <div style="font-size: 11px; color: #991b1b; text-transform: uppercase;">Crashes</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #fef3c7; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #d97706;">{$errors}</div>
                                        <div style="font-size: 11px; color: #92400e; text-transform: uppercase;">Errors</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #dbeafe; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #2563eb;">{$events['total']}</div>
                                        <div style="font-size: 11px; color: #1e40af; text-transform: uppercase;">Total</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #f3e8ff; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #7c3aed;">{$events['devices_affected']}</div>
                                        <div style="font-size: 11px; color: #5b21b6; text-transform: uppercase;">Devices</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background-color: #f9fafb; border-radius: 8px; padding: 25px; border-left: 4px solid #667eea;">
                                <div style="font-size: 14px; line-height: 1.7; color: #4b5563;">{$analysisHtml}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="{$dashboardUrl}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 6px; font-weight: 600; font-size: 14px;">View Dashboard</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #9ca3af; text-align: center;">
                                Generated by Vitalytics AI Analysis (Experimental) | Powered by Claude<br>
                                Only analyzing production data (excludes test data and dismissed events)
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Convert markdown-style text to HTML
     */
    protected function markdownToHtml(string $text): string
    {
        // Convert headers
        $text = preg_replace('/^## (.+)$/m', '<h3 style="margin: 20px 0 10px 0; font-size: 15px; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">$1</h3>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h2 style="margin: 15px 0 10px 0; font-size: 17px; color: #111827;">$1</h2>', $text);
        
        // Convert bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert bullet points  
        $text = preg_replace('/^[•\-] (.+)$/m', '<li style="margin: 4px 0;">$1</li>', $text);
        
        // Wrap consecutive li tags in ul
        $text = preg_replace('/(<li[^>]*>.*?<\/li>\s*)+/s', '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">$0</ul>', $text);
        
        // Convert numbered lists
        $text = preg_replace('/^(\d+)\. (.+)$/m', '<li style="margin: 4px 0;"><strong>$1.</strong> $2</li>', $text);
        
        // Convert line breaks
        $text = nl2br($text);
        
        // Clean up extra br tags around block elements
        $text = preg_replace('/<br\s*\/?>(\s*<[huo])/i', '$1', $text);
        $text = preg_replace('/(<\/[huo][^>]*>)\s*<br\s*\/?>/i', '$1', $text);
        
        return $text;
    }

    /**
     * Truncate string
     */
    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }

    // ==========================================
    // ANALYTICS ANALYSIS METHODS
    // ==========================================

    /**
     * Generate daily analytics analysis for a product
     */
    public function generateAnalyticsAnalysis(Product $product): ?string
    {
        $settings = ProductAlertSetting::forProduct($product->id);

        if (!$settings->ai_analysis_enabled) {
            return null;
        }

        // Get analytics data from last 24 hours
        $since = Carbon::now()->subHours(24);
        $data = $this->getAnalyticsData($product, $since);

        if ($data['total_events'] === 0) {
            Log::info("AiAnalysis: No analytics events for {$product->name}, skipping");
            return null;
        }

        // Generate analysis with Claude
        $analysis = $this->callClaudeApiForAnalytics($product, $data, $settings);

        if (!$analysis) {
            return null;
        }

        // Store the summary in the database
        $this->storeSummary($product, 'analytics', $analysis, $data);

        // Send email to subscribers
        $this->sendAnalyticsEmail($product, $analysis, $data, $settings);

        return $analysis;
    }

    /**
     * Store a summary in the database
     */
    protected function storeSummary(Product $product, string $type, string $content, array $data): void
    {
        try {
            AiSummary::create([
                'product_id' => $product->id,
                'type' => $type,
                'content' => $content,
                'summary_data' => $data,
                'period_start' => isset($data['period']['start']) ? Carbon::parse($data['period']['start']) : null,
                'period_end' => isset($data['period']['end']) ? Carbon::parse($data['period']['end']) : null,
                'generated_at' => Carbon::now(),
            ]);

            Log::info("AiAnalysis: Stored {$type} summary for {$product->name}");
        } catch (\Exception $e) {
            Log::error("AiAnalysis: Failed to store {$type} summary for {$product->name}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get analytics data for analysis
     */
    protected function getAnalyticsData(Product $product, Carbon $since): array
    {
        $appIdentifiers = $product->apps()->pluck('identifier')->toArray();

        if (empty($appIdentifiers)) {
            return ['total_events' => 0];
        }

        // Total events (excluding test data)
        $totalEvents = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->count();

        if ($totalEvents === 0) {
            return ['total_events' => 0];
        }

        // Unique sessions
        $uniqueSessions = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->distinct()
            ->count('session_id');

        // Unique users
        $uniqueUsers = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->distinct()
            ->count('anonymous_user_id');

        // Events by category
        $eventsByCategory = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->select('event_category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('event_category')
            ->orderByDesc('count')
            ->pluck('count', 'event_category')
            ->toArray();

        // Top events
        $topEvents = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->select('event_name', 'event_category', 'screen_name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_sessions')
            ->groupBy('event_name', 'event_category', 'screen_name')
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->toArray();

        // Top screens
        $topScreens = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->whereNotNull('screen_name')
            ->select('screen_name')
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_sessions')
            ->groupBy('screen_name')
            ->orderByDesc('event_count')
            ->limit(10)
            ->get()
            ->toArray();

        // Events by app
        $eventsByApp = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->select('app_identifier')
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('COUNT(DISTINCT session_id) as sessions')
            ->selectRaw('COUNT(DISTINCT anonymous_user_id) as users')
            ->groupBy('app_identifier')
            ->get()
            ->toArray();

        // Session metrics
        $sessionStats = DB::table('analytics_sessions')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('started_at', '>=', $since)
            ->where('is_test', false)
            ->selectRaw('AVG(duration_seconds) as avg_duration')
            ->selectRaw('AVG(screens_viewed) as avg_screens')
            ->selectRaw('AVG(event_count) as avg_events')
            ->selectRaw('MAX(duration_seconds) as max_duration')
            ->first();

        // Hourly distribution
        $hourlyDistribution = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', false)
            ->selectRaw('HOUR(event_timestamp) as hour')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // User engagement patterns (sessions with many events vs few)
        $engagementBuckets = DB::table('analytics_sessions')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('started_at', '>=', $since)
            ->where('is_test', false)
            ->selectRaw('
                CASE
                    WHEN event_count <= 3 THEN "low"
                    WHEN event_count <= 10 THEN "medium"
                    ELSE "high"
                END as engagement_level
            ')
            ->selectRaw('COUNT(*) as session_count')
            ->groupBy('engagement_level')
            ->pluck('session_count', 'engagement_level')
            ->toArray();

        // Previous day comparison
        $previousDayStart = $since->copy()->subHours(24);
        $previousDayEvents = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $previousDayStart)
            ->where('event_timestamp', '<', $since)
            ->where('is_test', false)
            ->count();

        $previousDaySessions = DB::table('analytics_events')
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('event_timestamp', '>=', $previousDayStart)
            ->where('event_timestamp', '<', $since)
            ->where('is_test', false)
            ->distinct()
            ->count('session_id');

        return [
            'total_events' => $totalEvents,
            'unique_sessions' => $uniqueSessions,
            'unique_users' => $uniqueUsers,
            'events_by_category' => $eventsByCategory,
            'top_events' => $topEvents,
            'top_screens' => $topScreens,
            'events_by_app' => $eventsByApp,
            'session_stats' => [
                'avg_duration' => (int) ($sessionStats->avg_duration ?? 0),
                'avg_screens' => round($sessionStats->avg_screens ?? 0, 1),
                'avg_events' => round($sessionStats->avg_events ?? 0, 1),
                'max_duration' => (int) ($sessionStats->max_duration ?? 0),
            ],
            'hourly_distribution' => $hourlyDistribution,
            'engagement_buckets' => $engagementBuckets,
            'previous_day' => [
                'events' => $previousDayEvents,
                'sessions' => $previousDaySessions,
            ],
            'period' => [
                'start' => $since->toIso8601String(),
                'end' => Carbon::now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Call Claude API for analytics analysis
     */
    protected function callClaudeApiForAnalytics(Product $product, array $data, ProductAlertSetting $settings): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('AiAnalysis: No Anthropic API key configured');
            return null;
        }

        $prompt = $this->buildAnalyticsPrompt($product, $data, $settings);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => 1500,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('AiAnalysis: Claude API error for analytics', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $responseData = $response->json();
            return $responseData['content'][0]['text'] ?? null;

        } catch (\Exception $e) {
            Log::error('AiAnalysis: Claude API exception for analytics', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build the prompt for analytics analysis
     */
    protected function buildAnalyticsPrompt(Product $product, array $data, ProductAlertSetting $settings): string
    {
        $timezone = $settings->timezone;
        $now = Carbon::now()->setTimezone($timezone)->format('M j, Y');

        // Format data sections
        $topEventsFormatted = $this->formatTopEventsForAnalytics($data['top_events']);
        $topScreensFormatted = $this->formatTopScreens($data['top_screens']);
        $categoryFormatted = $this->formatCategories($data['events_by_category']);
        $hourlyFormatted = $this->formatHourlyData($data['hourly_distribution']);
        $appBreakdown = $this->formatAppBreakdown($data['events_by_app']);

        // Session stats
        $avgDuration = $this->formatDuration($data['session_stats']['avg_duration']);
        $maxDuration = $this->formatDuration($data['session_stats']['max_duration']);
        $avgScreens = $data['session_stats']['avg_screens'];
        $avgEvents = $data['session_stats']['avg_events'];

        // Engagement
        $lowEngagement = $data['engagement_buckets']['low'] ?? 0;
        $mediumEngagement = $data['engagement_buckets']['medium'] ?? 0;
        $highEngagement = $data['engagement_buckets']['high'] ?? 0;

        // Comparison
        $eventChange = $data['previous_day']['events'] > 0
            ? round((($data['total_events'] - $data['previous_day']['events']) / $data['previous_day']['events']) * 100, 1)
            : 0;
        $sessionChange = $data['previous_day']['sessions'] > 0
            ? round((($data['unique_sessions'] - $data['previous_day']['sessions']) / $data['previous_day']['sessions']) * 100, 1)
            : 0;

        return <<<PROMPT
You are analyzing user analytics data for "{$product->name}" application. Generate a concise daily usage report for {$now}.

ANALYTICS DATA (Last 24 hours):
- Total Events: {$data['total_events']}
- Unique Sessions: {$data['unique_sessions']}
- Unique Users: {$data['unique_users']}

Session Metrics:
- Average Duration: {$avgDuration}
- Longest Session: {$maxDuration}
- Avg Screens per Session: {$avgScreens}
- Avg Events per Session: {$avgEvents}

User Engagement Breakdown:
- Low engagement (1-3 events): {$lowEngagement} sessions
- Medium engagement (4-10 events): {$mediumEngagement} sessions
- High engagement (11+ events): {$highEngagement} sessions

Comparison vs Yesterday:
- Events: {$eventChange}% change ({$data['previous_day']['events']} yesterday)
- Sessions: {$sessionChange}% change ({$data['previous_day']['sessions']} yesterday)

Events by Category:
{$categoryFormatted}

Top Events:
{$topEventsFormatted}

Most Visited Screens:
{$topScreensFormatted}

Platform Breakdown:
{$appBreakdown}

Hourly Distribution (24h format):
{$hourlyFormatted}

Please provide a report with:

1. **Engagement Score** (1-10) with assessment (Excellent/Good/Fair/Poor)

2. **Executive Summary** (2-3 sentences summarizing user activity)

3. **Key Insights** (bullet points of interesting patterns or behaviors)

4. **User Journey Highlights** (what screens/features are most used, user flow patterns)

5. **Engagement Patterns** (when users are most active, session quality observations)

6. **Recommendations** (actionable suggestions to improve engagement or UX)

7. **Day-over-Day Comparison** (brief note on trends)

Keep the report concise but actionable. Focus on what the data tells us about how users are actually using the app. Use plain text formatting suitable for email.
PROMPT;
    }

    /**
     * Format top events for analytics prompt
     */
    protected function formatTopEventsForAnalytics(array $events): string
    {
        if (empty($events)) {
            return "No events recorded";
        }

        $lines = [];
        foreach ($events as $event) {
            $screen = $event->screen_name ? " on {$event->screen_name}" : '';
            $lines[] = sprintf(
                "- %s [%s]%s (%d times, %d sessions)",
                $event->event_name,
                $event->event_category,
                $screen,
                $event->count,
                $event->unique_sessions
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Format top screens
     */
    protected function formatTopScreens(array $screens): string
    {
        if (empty($screens)) {
            return "No screen data";
        }

        $lines = [];
        foreach ($screens as $screen) {
            $lines[] = sprintf(
                "- %s (%d events, %d sessions)",
                $screen->screen_name,
                $screen->event_count,
                $screen->unique_sessions
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Format categories
     */
    protected function formatCategories(array $categories): string
    {
        if (empty($categories)) {
            return "No category data";
        }

        $total = array_sum($categories);
        $lines = [];
        foreach ($categories as $category => $count) {
            $percentage = round(($count / $total) * 100, 1);
            $lines[] = "- {$category}: {$count} ({$percentage}%)";
        }
        return implode("\n", $lines);
    }

    /**
     * Format app breakdown
     */
    protected function formatAppBreakdown(array $apps): string
    {
        if (empty($apps)) {
            return "No app data";
        }

        $lines = [];
        foreach ($apps as $app) {
            $lines[] = sprintf(
                "- %s: %d events, %d sessions, %d users",
                $app->app_identifier,
                $app->event_count,
                $app->sessions,
                $app->users
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Format duration in seconds to readable string
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }

    /**
     * Send analytics analysis email to subscribers
     */
    protected function sendAnalyticsEmail(
        Product $product,
        string $analysis,
        array $data,
        ProductAlertSetting $settings
    ): void {
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->enabled()
            ->critical()
            ->with('user')
            ->get();

        if ($subscribers->isEmpty()) {
            Log::info("AiAnalysis: No subscribers for analytics report {$product->name}");
            return;
        }

        $date = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y');
        $subject = "[Analytics Report] {$product->name} - User Activity for {$date}";

        $body = $this->buildAnalyticsEmailBody($product, $analysis, $data, $settings);

        foreach ($subscribers as $subscriber) {
            $email = $subscriber->email_address;

            if (!$email) {
                continue;
            }

            try {
                Mail::html($body, function ($mail) use ($email, $subject, $subscriber) {
                    $mail->to($email, $subscriber->display_name)
                        ->subject($subject);
                });

                Log::info("AiAnalysis: Sent analytics report to {$email} for {$product->name}");
            } catch (\Exception $e) {
                Log::error('AiAnalysis: Analytics email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build the analytics email body as HTML
     */
    protected function buildAnalyticsEmailBody(
        Product $product,
        string $analysis,
        array $data,
        ProductAlertSetting $settings
    ): string {
        $date = Carbon::now()->setTimezone($settings->timezone)->format('M j, Y g:i A T');
        $dashboardUrl = config('app.url') . '/?mode=analytics';
        $productName = htmlspecialchars($product->name);

        // Calculate engagement score for display
        $totalSessions = ($data['engagement_buckets']['low'] ?? 0)
            + ($data['engagement_buckets']['medium'] ?? 0)
            + ($data['engagement_buckets']['high'] ?? 0);
        $highEngagementPct = $totalSessions > 0
            ? round((($data['engagement_buckets']['high'] ?? 0) / $totalSessions) * 100)
            : 0;

        $avgDuration = $this->formatDuration($data['session_stats']['avg_duration']);

        // Convert markdown-style analysis to HTML
        $analysisHtml = $this->markdownToHtml($analysis);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Report - {$productName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); padding: 30px; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Analytics Report</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">{$productName}</p>
                            <p style="color: rgba(255,255,255,0.7); margin: 5px 0 0 0; font-size: 13px;">Generated: {$date}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #ecfeff; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #0891b2;">{$data['total_events']}</div>
                                        <div style="font-size: 11px; color: #0e7490; text-transform: uppercase;">Events</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #f0fdfa; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #0d9488;">{$data['unique_sessions']}</div>
                                        <div style="font-size: 11px; color: #0f766e; text-transform: uppercase;">Sessions</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #faf5ff; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #7c3aed;">{$data['unique_users']}</div>
                                        <div style="font-size: 11px; color: #5b21b6; text-transform: uppercase;">Users</div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="23%" style="text-align: center; padding: 15px; background-color: #fff7ed; border-radius: 8px;">
                                        <div style="font-size: 28px; font-weight: bold; color: #ea580c;">{$avgDuration}</div>
                                        <div style="font-size: 11px; color: #c2410c; text-transform: uppercase;">Avg Duration</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background-color: #f9fafb; border-radius: 8px; padding: 25px; border-left: 4px solid #0891b2;">
                                <div style="font-size: 14px; line-height: 1.7; color: #4b5563;">{$analysisHtml}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="{$dashboardUrl}" style="display: inline-block; background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 6px; font-weight: 600; font-size: 14px;">View Analytics Dashboard</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #9ca3af; text-align: center;">
                                Generated by Vitalytics AI Analysis (Experimental) | Powered by Claude<br>
                                Only analyzing production data (excludes test data)
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
