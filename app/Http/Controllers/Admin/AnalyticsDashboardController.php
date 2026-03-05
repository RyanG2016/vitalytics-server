<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductIcon;
use App\Models\Product;
use App\Models\App;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\EventLabelMapping;
use App\Models\AiSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsDashboardController extends Controller
{
    /**
     * Get the show_test setting from request or session
     * If passed in request, update the session value
     */
    private function getShowTest(Request $request): bool
    {
        // If explicitly set in request, update session
        if ($request->has('hours') || $request->has('product') || $request->has('show_test')) {
            $showTest = $request->boolean('show_test');
            session(['show_test' => $showTest]);
            return $showTest;
        }

        // Otherwise return session value (default false)
        return session('show_test', false);
    }

    /**
     * Get all parent products from database, filtered by user permissions
     */
    private function getProducts(): array
    {
        $products = Product::with('apps')->active()->ordered()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();
        $customIcons = ProductIcon::getIconsMap();
        $result = [];

        foreach ($products as $product) {
            $productId = $product->slug;
            
            // Only include products the user has access to
            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }

            $custom = $customIcons[$productId] ?? null;

            $result[$productId] = [
                'name' => $product->name,
                'color' => $custom['color'] ?? $product->color ?? '#666',
                'icon' => $product->icon ?? 'fa-cube',
                'custom_icon' => $custom['icon'] ?? null,
                'has_custom_icon' => $custom['has_custom'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Get all configured apps from database, filtered by product and user permissions
     */
    private function getApps(?string $productSlug = null): array
    {
        $apps = [];
        $products = Product::with('apps')->active()->ordered()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();

        foreach ($products as $product) {
            $productId = $product->slug;
            
            // Skip if user doesn't have access to this product
            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }
            
            // Skip if filtering by product and this isn't the one
            if ($productSlug && $productSlug !== 'all' && $productId !== $productSlug) {
                continue;
            }
            
            foreach ($product->apps as $app) {
                $apps[$app->identifier] = [
                    'name' => $app->name,
                    'icon' => $app->platform_icon,
                    'product' => $productId,
                    'product_name' => $product->name,
                ];
            }
        }

        return $apps;
    }

    /**
     * Display the analytics dashboard (Health or Analytics mode)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $hours = $request->input('hours', 24);
        $product = $request->input('product');
        $showTest = $this->getShowTest($request);
        $mode = $request->input('mode', session('dashboard_mode', 'health'));

        // Check dashboard access permissions for viewers
        if (!$user->isAdmin()) {
            $canHealth = $user->canAccessHealth();
            $canAnalytics = $user->canAccessAnalytics();

            // If user doesn't have access to requested mode, redirect to allowed mode
            if ($mode === 'health' && !$canHealth && $canAnalytics) {
                $mode = 'analytics';
            } elseif ($mode === 'analytics' && !$canAnalytics && $canHealth) {
                $mode = 'health';
            } elseif (!$canHealth && !$canAnalytics) {
                abort(403, 'You do not have access to any dashboards.');
            }
        }

        // Store mode in session
        if ($request->has('mode')) {
            session(['dashboard_mode' => $mode]);
        }

        // Use UTC for $since because event_timestamp/started_at are stored in UTC
        $since = now('UTC')->subHours($hours);
        $apps = $this->getApps($product);
        $products = $this->getProducts();
        $appIdentifiers = array_keys($apps);

        // Return analytics view if in analytics mode
        if ($mode === 'analytics') {
            return $this->analyticsIndex($request, $hours, $product, $showTest, $since, $apps, $products, $appIdentifiers);
        }

        // Health monitoring mode (default)
        $appStats = [];
        foreach ($apps as $appId => $appInfo) {
            $appStats[$appId] = $this->getAppStats($appId, $since, $showTest);
            $appStats[$appId]['info'] = $appInfo;
        }

        $productStats = $this->getProductStats($since, $showTest, $product);
        $overallStats = $this->getOverallStats($since, $appIdentifiers, $showTest);
        $recentEvents = $this->getRecentEvents($since, 20, $appIdentifiers, $showTest);
        $hourlyTrends = $this->getHourlyTrends($hours, $appIdentifiers, $showTest);
        $topErrors = $this->getTopErrors($since, 10, $appIdentifiers, $showTest);

        return view('admin.analytics.index', [
            'appStats' => $appStats,
            'productStats' => $productStats,
            'overallStats' => $overallStats,
            'recentEvents' => $recentEvents,
            'hourlyTrends' => $hourlyTrends,
            'topErrors' => $topErrors,
            'hours' => $hours,
            'product' => $product,
            'products' => $products,
            'showTest' => $showTest,
            'mode' => 'health',
            'lastUpdated' => now()->format('M d, Y g:i:s A'),
        ]);
    }

    /**
     * Display the analytics tracking dashboard
     */
    private function analyticsIndex(Request $request, int $hours, ?string $product, bool $showTest, Carbon $since, array $apps, array $products, array $appIdentifiers)
    {
        // Get analytics stats for products
        $productStats = $this->getAnalyticsProductStats($since, $showTest, $product);

        // Get overall analytics stats
        $overallStats = $this->getAnalyticsOverallStats($since, $appIdentifiers, $showTest);

        // Get top events
        $topEvents = $this->getTopAnalyticsEvents($since, 15, $appIdentifiers, $showTest);

        // Get recent sessions
        $recentSessions = $this->getRecentSessions($since, 10, $appIdentifiers, $showTest);

        // Get hourly event trends
        $hourlyTrends = $this->getAnalyticsHourlyTrends($hours, $appIdentifiers, $showTest);

        // Get events by category
        $eventsByCategory = $this->getEventsByCategory($since, $appIdentifiers, $showTest);

        // Get unread feedback count
        $feedbackUnread = \App\Models\ProductFeedback::query()
            ->whereIn('app_identifier', $appIdentifiers)
            ->where('is_read', false)
            ->when(!$showTest, fn($q) => $q->where('is_test', false))
            ->count();

        return view('admin.analytics.analytics-index', [
            'productStats' => $productStats,
            'overallStats' => $overallStats,
            'topEvents' => $topEvents,
            'recentSessions' => $recentSessions,
            'hourlyTrends' => $hourlyTrends,
            'eventsByCategory' => $eventsByCategory,
            'feedbackUnread' => $feedbackUnread,
            'hours' => $hours,
            'product' => $product,
            'products' => $products,
            'showTest' => $showTest,
            'mode' => 'analytics',
            'lastUpdated' => now()->format('M d, Y g:i:s A'),
        ]);
    }

    /**
     * Get analytics stats for each product with sub-product breakdown
     */
    private function getAnalyticsProductStats(Carbon $since, bool $showTest = false, ?string $filterProduct = null): array
    {
        $products = Product::with('apps')->active()->ordered()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();
        $customIcons = ProductIcon::getIconsMap();
        $result = [];

        foreach ($products as $product) {
            $productId = $product->slug;

            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }

            if ($filterProduct && $filterProduct !== 'all' && $productId !== $filterProduct) {
                continue;
            }

            $custom = $customIcons[$productId] ?? null;

            $productStats = [
                'name' => $product->name,
                'color' => $custom['color'] ?? $product->color ?? '#666',
                'icon' => $product->icon ?? 'fa-cube',
                'custom_icon' => $custom['icon'] ?? null,
                'has_custom_icon' => $custom['has_custom'] ?? false,
                'totalEvents' => 0,
                'uniqueSessions' => 0,
                'uniqueUsers' => 0,
                'avgSessionDuration' => 0,
                'screensPerSession' => 0,
                'subProducts' => [],
            ];

            $allSessionDurations = [];
            $allScreensViewed = [];

            foreach ($product->apps as $app) {
                $appId = $app->identifier;
                $appStats = $this->getAnalyticsAppStats($appId, $since, $showTest);
                $appStats['info'] = [
                    'name' => $app->name,
                    'icon' => $app->platform_icon,
                    'product' => $productId,
                ];

                $productStats['totalEvents'] += $appStats['totalEvents'];
                $productStats['uniqueSessions'] += $appStats['uniqueSessions'];
                $productStats['uniqueUsers'] += $appStats['uniqueUsers'];

                if ($appStats['avgSessionDuration'] > 0) {
                    $allSessionDurations[] = $appStats['avgSessionDuration'];
                }
                if ($appStats['screensPerSession'] > 0) {
                    $allScreensViewed[] = $appStats['screensPerSession'];
                }

                $productStats['subProducts'][$appId] = $appStats;
            }

            // Calculate averages for product
            if (!empty($allSessionDurations)) {
                $productStats['avgSessionDuration'] = round(array_sum($allSessionDurations) / count($allSessionDurations));
            }
            if (!empty($allScreensViewed)) {
                $productStats['screensPerSession'] = round(array_sum($allScreensViewed) / count($allScreensViewed), 1);
            }

            $result[$productId] = $productStats;
        }

        return $result;
    }

    /**
     * Get analytics stats for a specific app
     */
    private function getAnalyticsAppStats(string $appIdentifier, Carbon $since, bool $showTest = false): array
    {
        $query = DB::table('analytics_events')
            ->where('app_identifier', $appIdentifier)
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', $showTest);

        $totalEvents = (clone $query)->count();
        $uniqueSessions = (clone $query)->distinct('session_id')->count('session_id');
        $uniqueUsers = (clone $query)->distinct('anonymous_user_id')->count('anonymous_user_id');

        // Get session stats
        $sessionQuery = DB::table('analytics_sessions')
            ->where('app_identifier', $appIdentifier)
            ->where('started_at', '>=', $since)
            ->where('is_test', $showTest);

        $sessionStats = $sessionQuery->selectRaw('AVG(duration_seconds) as avg_duration, AVG(screens_viewed) as avg_screens')
            ->first();

        // Get top 3 events for this app
        $topEvents = (clone $query)
            ->select('event_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('count', 'event_name')
            ->toArray();

        return [
            'totalEvents' => $totalEvents,
            'uniqueSessions' => $uniqueSessions,
            'uniqueUsers' => $uniqueUsers,
            'avgSessionDuration' => (int) ($sessionStats->avg_duration ?? 0),
            'screensPerSession' => round($sessionStats->avg_screens ?? 0, 1),
            'topEvents' => $topEvents,
        ];
    }

    /**
     * Get overall analytics stats
     */
    private function getAnalyticsOverallStats(Carbon $since, array $appIdentifiers = [], bool $showTest = false): array
    {
        $query = DB::table('analytics_events')
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        $totalEvents = (clone $query)->count();
        $uniqueSessions = (clone $query)->distinct('session_id')->count('session_id');
        $uniqueUsers = (clone $query)->distinct('anonymous_user_id')->count('anonymous_user_id');

        // Separate metrics for identified vs anonymous data
        $identifiedDevices = (clone $query)->whereNotNull('device_id')->distinct('device_id')->count('device_id');
        $anonymousSessions = (clone $query)->whereNull('device_id')->distinct('session_id')->count('session_id');

        // Get session stats
        $sessionQuery = DB::table('analytics_sessions')
            ->where('started_at', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $sessionQuery->whereIn('app_identifier', $appIdentifiers);
        }

        $sessionStats = $sessionQuery->selectRaw('AVG(duration_seconds) as avg_duration, AVG(screens_viewed) as avg_screens')
            ->first();

        $activeSessions = DB::table('analytics_sessions')
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->whereNull('ended_at')
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $activeSessions->whereIn('app_identifier', $appIdentifiers);
        }

        // Calculate consent breakdown percentages
        $totalTrackingSessions = $identifiedDevices + $anonymousSessions;
        $standardPercentage = $totalTrackingSessions > 0
            ? round(($identifiedDevices / $totalTrackingSessions) * 100, 1)
            : 0;
        $privacyPercentage = $totalTrackingSessions > 0
            ? round(($anonymousSessions / $totalTrackingSessions) * 100, 1)
            : 0;

        return [
            'totalEvents' => $totalEvents,
            'uniqueSessions' => $uniqueSessions,
            'uniqueUsers' => $uniqueUsers,
            'identifiedDevices' => $identifiedDevices,
            'anonymousSessions' => $anonymousSessions,
            'standardPercentage' => $standardPercentage,
            'privacyPercentage' => $privacyPercentage,
            'avgSessionDuration' => (int) ($sessionStats->avg_duration ?? 0),
            'screensPerSession' => round($sessionStats->avg_screens ?? 0, 1),
            'activeSessions' => $activeSessions->count(),
        ];
    }

    /**
     * Get top analytics events
     */
    private function getTopAnalyticsEvents(Carbon $since, int $limit = 15, array $appIdentifiers = [], bool $showTest = false): array
    {
        $query = DB::table('analytics_events')
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        return $query->select('event_name', 'event_category', 'screen_name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_sessions')
            ->groupBy('event_name', 'event_category', 'screen_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'name' => $e->event_name,
                'category' => $e->event_category,
                'screen' => $e->screen_name,
                'count' => $e->count,
                'uniqueSessions' => $e->unique_sessions,
            ])
            ->toArray();
    }

    /**
     * Get recent sessions
     */
    private function getRecentSessions(Carbon $since, int $limit = 10, array $appIdentifiers = [], bool $showTest = false): array
    {
        $query = DB::table('analytics_sessions')
            ->where('started_at', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        return $query->orderByDesc('last_activity_at')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'session_id' => $s->session_id,
                'app_identifier' => $s->app_identifier,
                'device_id' => $s->device_id,
                'platform' => $s->platform,
                'app_version' => $s->app_version,
                // started_at is stored in local timezone (via Laravel's timestamp handling)
                'started_at' => Carbon::parse($s->started_at)->diffForHumans(),
                'started_at_time' => Carbon::parse($s->started_at)->format('g:i A'),
                'started_at_full' => Carbon::parse($s->started_at)->format('M d, Y g:i:s A'),
                'duration_seconds' => $s->duration_seconds,
                'duration_formatted' => $this->formatDuration($s->duration_seconds),
                'event_count' => $s->event_count,
                'screens_viewed' => $s->screens_viewed,
                'is_active' => $s->last_activity_at && Carbon::parse($s->last_activity_at)->isAfter(now()->subMinutes(30)) && !$s->ended_at,
            ])
            ->toArray();
    }

    /**
     * Get analytics hourly trends
     */
    private function getAnalyticsHourlyTrends(int $hours, array $appIdentifiers = [], bool $showTest = false): array
    {
        $trends = [];
        $now = now();

        for ($i = $hours - 1; $i >= 0; $i--) {
            $hourStart = $now->copy()->subHours($i)->startOfHour();
            $hourEnd = $hourStart->copy()->endOfHour();

            $query = DB::table('analytics_events')
                ->whereBetween('event_timestamp', [$hourStart, $hourEnd])
                ->where('is_test', $showTest);

            if (!empty($appIdentifiers)) {
                $query->whereIn('app_identifier', $appIdentifiers);
            }

            $count = $query->count();
            $sessions = (clone $query)->distinct('session_id')->count('session_id');

            $trends[] = [
                'hour' => $hourStart->format('H:i'),
                'events' => $count,
                'sessions' => $sessions,
            ];
        }

        return $trends;
    }

    /**
     * Get events grouped by category
     */
    private function getEventsByCategory(Carbon $since, array $appIdentifiers = [], bool $showTest = false): array
    {
        $query = DB::table('analytics_events')
            ->where('event_timestamp', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        return $query->select('event_category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('event_category')
            ->orderByDesc('count')
            ->get()
            ->map(fn($c) => [
                'category' => $c->event_category,
                'count' => $c->count,
            ])
            ->toArray();
    }

    /**
     * Format duration in seconds to human readable
     */
    private function formatDuration(?int $seconds): string
    {
        if (!$seconds || $seconds < 1) {
            return '0s';
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Session explorer - view events in a session
     */
    public function sessionExplorer(Request $request, string $sessionId)
    {
        $showTest = $this->getShowTest($request);
        $accessibleApps = array_keys($this->getApps());

        // Get session
        $session = DB::table('analytics_sessions')
            ->where('session_id', $sessionId)
            ->whereIn('app_identifier', $accessibleApps)
            ->first();

        if (!$session) {
            abort(404);
        }

        // Get events in this session ordered by timestamp
        $rawEvents = DB::table('analytics_events')
            ->where('session_id', $sessionId)
            ->orderBy('event_timestamp')
            ->get();

        // Process events and group by screen
        $events = [];
        $screenGroups = [];
        $currentScreen = null;
        $currentScreenKey = 0;
        $previousTimestamp = null;

        foreach ($rawEvents as $index => $e) {
            // Parse UTC timestamp and convert to local timezone for display
            $timestamp = Carbon::parse($e->event_timestamp, 'UTC');
            $localTimestamp = $timestamp->copy()->setTimezone(config('app.timezone'));

            // Calculate time delta from previous event
            $timeDelta = null;
            $timeDeltaSeconds = 0;
            if ($previousTimestamp) {
                $timeDeltaSeconds = $timestamp->diffInSeconds($previousTimestamp);
                $timeDelta = $this->formatTimeDelta($timeDeltaSeconds);
            }

            $event = [
                'id' => $e->id,
                'event_id' => $e->event_id,
                'name' => $e->event_name,
                'category' => $e->event_category,
                'screen' => $e->screen_name,
                'element' => $e->element_id,
                'properties' => $e->properties ? json_decode($e->properties, true) : null,
                'duration_ms' => $e->duration_ms,
                'timestamp' => $localTimestamp->format('g:i:s A'),
                'timestamp_full' => $localTimestamp->format('M d, Y g:i:s A'),
                'timestamp_raw' => $timestamp->toIso8601String(),
                'time_delta' => $timeDelta,
                'time_delta_seconds' => $timeDeltaSeconds,
            ];

            $events[] = $event;

            // Group by screen for journey view
            $screenName = $e->screen_name ?: '(No Screen)';

            // Check if we need to start a new screen group
            if ($screenName !== $currentScreen) {
                $currentScreen = $screenName;
                $currentScreenKey++;
                $screenGroups[$currentScreenKey] = [
                    'screen' => $screenName,
                    'start_time' => $localTimestamp->format('g:i:s A'),
                    'start_timestamp' => $timestamp,
                    'events' => [],
                    'duration' => null,
                    'duration_seconds' => 0,
                ];
            }

            $screenGroups[$currentScreenKey]['events'][] = $event;
            $screenGroups[$currentScreenKey]['end_timestamp'] = $timestamp;

            $previousTimestamp = $timestamp;
        }

        // Calculate duration for each screen group and time since previous screen
        $previousEndTimestamp = null;
        foreach ($screenGroups as $key => &$group) {
            if (isset($group['start_timestamp']) && isset($group['end_timestamp'])) {
                $durationSeconds = $group['end_timestamp']->diffInSeconds($group['start_timestamp']);
                $group['duration_seconds'] = $durationSeconds;
                $group['duration'] = $this->formatTimeDelta($durationSeconds);

                // Calculate time since previous screen ended
                if ($previousEndTimestamp) {
                    $timeSincePrevious = $group['start_timestamp']->diffInSeconds($previousEndTimestamp);
                    $group['time_since_previous'] = $this->formatTimeDelta($timeSincePrevious);
                } else {
                    $group['time_since_previous'] = null;
                }

                $previousEndTimestamp = $group['end_timestamp'];
            }
            // Clean up Carbon objects before passing to view
            unset($group['start_timestamp'], $group['end_timestamp']);
        }

        // Load server-side label mappings for this app
        $labelMappings = EventLabelMapping::getMappingsForApp($session->app_identifier);

        return view('admin.analytics.session-explorer', [
            'session' => $session,
            'events' => $events,
            'screenGroups' => array_values($screenGroups),
            'showTest' => $showTest,
            'products' => $this->getProducts(),
            'labelMappings' => $labelMappings,
        ]);
    }

    /**
     * Format time delta in human readable format
     */
    private function formatTimeDelta(int $seconds): string
    {
        if ($seconds < 1) {
            return '<1s';
        } elseif ($seconds < 60) {
            return "+{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $secs > 0 ? "+{$minutes}m {$secs}s" : "+{$minutes}m";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $minutes > 0 ? "+{$hours}h {$minutes}m" : "+{$hours}h";
        }
    }

    /**
     * Sessions list - all sessions with pagination
     */
    public function sessions(Request $request)
    {
        $hours = $request->input('hours', 24);
        $product = $request->input('product');
        $showTest = $this->getShowTest($request);
        $since = now('UTC')->subHours($hours);
        $apps = $this->getApps($product);
        $appIdentifiers = array_keys($apps);

        $query = DB::table('analytics_sessions')
            ->where('started_at', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        // Get sessions with pagination
        $sessions = $query
            ->orderByDesc('last_activity_at')
            ->paginate(30)
            ->through(function ($s) {
                return [
                    'id' => $s->id,
                    'session_id' => $s->session_id,
                    'app_identifier' => $s->app_identifier,
                    'device_id' => $s->device_id,
                    'platform' => $s->platform,
                    'app_version' => $s->app_version,
                    'started_at' => Carbon::parse($s->started_at)->diffForHumans(),
                    'started_at_time' => Carbon::parse($s->started_at)->format('g:i A'),
                    'started_at_date' => Carbon::parse($s->started_at)->format('M d, Y'),
                    'started_at_full' => Carbon::parse($s->started_at)->format('M d, Y g:i:s A'),
                    'duration_seconds' => $s->duration_seconds,
                    'duration_formatted' => $this->formatDuration($s->duration_seconds),
                    'event_count' => $s->event_count,
                    'screens_viewed' => $s->screens_viewed,
                    'country' => $s->country,
                    'region' => $s->region ?? null,
                    'city' => $s->city ?? null,
                    'latitude' => $s->latitude ?? null,
                    'longitude' => $s->longitude ?? null,
                    'is_active' => $s->last_activity_at && Carbon::parse($s->last_activity_at)->isAfter(now()->subMinutes(30)) && !$s->ended_at,
                ];
            });

        // Get summary stats
        $statsQuery = DB::table('analytics_sessions')
            ->where('started_at', '>=', $since)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $statsQuery->whereIn('app_identifier', $appIdentifiers);
        }

        $totalSessions = (clone $statsQuery)->count();
        $activeSessions = (clone $statsQuery)
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->whereNull('ended_at')
            ->count();

        $avgStats = (clone $statsQuery)
            ->selectRaw('AVG(duration_seconds) as avg_duration, AVG(event_count) as avg_events, AVG(screens_viewed) as avg_screens')
            ->first();

        return view('admin.analytics.sessions', [
            'sessions' => $sessions,
            'totalSessions' => $totalSessions,
            'activeSessions' => $activeSessions,
            'avgDuration' => (int) ($avgStats->avg_duration ?? 0),
            'avgEvents' => round($avgStats->avg_events ?? 0, 1),
            'avgScreens' => round($avgStats->avg_screens ?? 0, 1),
            'hours' => $hours,
            'product' => $product,
            'products' => $this->getProducts(),
            'apps' => $apps,
            'showTest' => $showTest,
        ]);
    }

    /**
     * Screen activity - events grouped by screen
     */
    public function screenActivity(Request $request)
    {
        $hours = $request->input('hours', 24);
        $product = $request->input('product');
        $showTest = $this->getShowTest($request);
        $since = now('UTC')->subHours($hours);
        $apps = $this->getApps($product);
        $appIdentifiers = array_keys($apps);

        $query = DB::table('analytics_events')
            ->where('event_timestamp', '>=', $since)
            ->whereNotNull('screen_name')
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        // Get screen stats
        $screens = $query->select('screen_name', 'app_identifier')
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_sessions')
            ->selectRaw('COUNT(DISTINCT anonymous_user_id) as unique_users')
            ->groupBy('screen_name', 'app_identifier')
            ->orderByDesc('event_count')
            ->limit(50)
            ->get()
            ->toArray();

        // Get events breakdown for each screen
        $screenDetails = [];
        foreach ($screens as $screen) {
            $eventsQuery = DB::table('analytics_events')
                ->where('event_timestamp', '>=', $since)
                ->where('screen_name', $screen->screen_name)
                ->where('app_identifier', $screen->app_identifier)
                ->where('is_test', $showTest);

            $screenDetails[$screen->screen_name . '_' . $screen->app_identifier] = $eventsQuery
                ->select('event_name', 'event_category')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('event_name', 'event_category')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray();
        }

        return view('admin.analytics.screen-activity', [
            'screens' => $screens,
            'screenDetails' => $screenDetails,
            'hours' => $hours,
            'product' => $product,
            'products' => $this->getProducts(),
            'showTest' => $showTest,
            'apps' => $apps,
        ]);
    }

    /**
     * Get aggregated stats for each product with sub-product breakdown
     */
    private function getProductStats(Carbon $since, bool $showTest = false, ?string $filterProduct = null): array
    {
        $products = Product::with('apps')->active()->ordered()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();
        $customIcons = ProductIcon::getIconsMap();
        $result = [];

        foreach ($products as $product) {
            $productId = $product->slug;
            
            // Skip if user doesn't have access
            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }

            // Skip if filtering by specific product
            if ($filterProduct && $filterProduct !== 'all' && $productId !== $filterProduct) {
                continue;
            }

            $custom = $customIcons[$productId] ?? null;

            // Initialize product stats
            $productStats = [
                'name' => $product->name,
                'color' => $custom['color'] ?? $product->color ?? '#666',
                'icon' => $product->icon ?? 'fa-cube',
                'custom_icon' => $custom['icon'] ?? null,
                'has_custom_icon' => $custom['has_custom'] ?? false,
                'crashes' => 0,
                'errors' => 0,
                'warnings' => 0,
                'networkErrors' => 0,
                'heartbeats' => 0,
                'infoEvents' => 0,
                'totalEvents' => 0,
                'activeDevices' => 0,
                'healthScore' => 100,
                'status' => 'healthy',
                'subProducts' => [],
            ];

            // Get stats for each sub-product (app)
            foreach ($product->apps as $app) {
                $appId = $app->identifier;
                $appStats = $this->getAppStats($appId, $since, $showTest);
                $infoEventCount = $appStats['info']; // Store info event count before overwriting
                $appStats['info'] = [
                    'name' => $app->name,
                    'icon' => $app->platform_icon,
                    'product' => $productId,
                ];

                // Add to product totals
                $productStats['crashes'] += $appStats['crashes'];
                $productStats['errors'] += $appStats['errors'];
                $productStats['warnings'] += $appStats['warnings'];
                $productStats['networkErrors'] += $appStats['networkErrors'];
                $productStats['heartbeats'] += $appStats['heartbeats'];
                $productStats['infoEvents'] += $infoEventCount;
                $productStats['totalEvents'] += $appStats['totalEvents'];
                $productStats['activeDevices'] += $appStats['activeDevices'];

                $productStats['subProducts'][$appId] = $appStats;
            }

            // Calculate product health score and status
            if ($productStats['totalEvents'] === 0) {
                // No data received - unknown/inactive status
                $productStats['healthScore'] = 0;
                $productStats['status'] = 'no_data';
            } else {
                $productStats['healthScore'] = max(0, 100 - ($productStats['crashes'] * 10) - ($productStats['errors'] * 2) - ($productStats['warnings'] * 0.5));
                $productStats['healthScore'] = round($productStats['healthScore']);
                $productStats['status'] = $productStats['healthScore'] >= 80 ? 'healthy' : ($productStats['healthScore'] >= 50 ? 'degraded' : 'critical');
            }

            $result[$productId] = $productStats;
        }

        return $result;
    }

    /**
     * Get stats for a specific app
     */
    private function getAppStats(string $appIdentifier, Carbon $since, bool $showTest = false): array
    {
        // $since is in UTC (for event_timestamp), compute local equivalent for created_at
        $sinceLocal = $since->copy()->setTimezone(config('app.timezone'));

        $query = DB::table('health_events')
            ->where('app_identifier', $appIdentifier)
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereNull('dismissed_at')
            ->where('is_test', $showTest);

        $events = $query->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $devicesQuery = DB::table('health_events')
            ->where('app_identifier', $appIdentifier)
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereNull('dismissed_at')
            ->where('is_test', $showTest);

        $activeDevices = $devicesQuery->distinct('device_id')
            ->count('device_id');

        $crashCount = $events['crash'] ?? 0;
        $errorCount = $events['error'] ?? 0;
        $warningCount = $events['warning'] ?? 0;
        $networkErrorCount = $events['networkError'] ?? 0;
        $heartbeatCount = $events['heartbeat'] ?? 0;
        $infoCount = $events['info'] ?? 0;

        // Calculate health score
        $healthScore = max(0, 100 - ($crashCount * 10) - ($errorCount * 2) - ($warningCount * 0.5));

        return [
            'crashes' => $crashCount,
            'errors' => $errorCount,
            'warnings' => $warningCount,
            'networkErrors' => $networkErrorCount,
            'heartbeats' => $heartbeatCount,
            'info' => $infoCount,
            'totalEvents' => array_sum($events),
            'activeDevices' => $activeDevices,
            'healthScore' => round($healthScore),
            'status' => $healthScore >= 80 ? 'healthy' : ($healthScore >= 50 ? 'degraded' : 'critical'),
        ];
    }

    /**
     * Get overall stats across all apps (or filtered apps)
     */
    private function getOverallStats(Carbon $since, array $appIdentifiers = [], bool $showTest = false): array
    {
        // $since is in UTC (for event_timestamp), compute local equivalent for created_at
        $sinceLocal = $since->copy()->setTimezone(config('app.timezone'));

        $query = DB::table('health_events')
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereNull('dismissed_at')
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        $events = $query->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $devicesQuery = DB::table('health_events')
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereNull('dismissed_at')
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $devicesQuery->whereIn('app_identifier', $appIdentifiers);
        }

        $totalDevices = $devicesQuery->distinct('device_id')
            ->count('device_id');

        $totalEvents = array_sum($events);

        return [
            'totalEvents' => $totalEvents,
            'crashes' => $events['crash'] ?? 0,
            'errors' => $events['error'] ?? 0,
            'warnings' => $events['warning'] ?? 0,
            'networkErrors' => $events['networkError'] ?? 0,
            'heartbeats' => $events['heartbeat'] ?? 0,
            'totalDevices' => $totalDevices,
        ];
    }

    /**
     * Get recent events
     */
    private function getRecentEvents(Carbon $since, int $limit = 20, array $appIdentifiers = [], bool $showTest = false): array
    {
        // $since is in UTC (for event_timestamp), compute local equivalent for created_at
        $sinceLocal = $since->copy()->setTimezone(config('app.timezone'));

        $query = DB::table('health_events')
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereIn('level', ['crash', 'error', 'warning', 'networkError'])
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        return $query->orderBy('event_timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'app' => $event->app_identifier,
                    'level' => $event->level,
                    'message' => $event->message,
                    'device_id' => $event->device_id,
                    'device_model' => $event->device_model,
                    'app_version' => $event->app_version,
                    'os_version' => $event->os_version,
                    'platform' => $event->platform,
                    'is_test' => $event->is_test ?? false,
                    'dismissed_at' => $event->dismissed_at,
                    'timestamp' => Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'))->diffForHumans(),
                    'timestamp_full' => Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y g:i:s A'),
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly trend data
     */
    private function getHourlyTrends(int $hours, array $appIdentifiers = [], bool $showTest = false): array
    {
        $trends = [];
        $now = now();

        for ($i = $hours - 1; $i >= 0; $i--) {
            $hourStart = $now->copy()->subHours($i)->startOfHour();
            $hourEnd = $hourStart->copy()->endOfHour();

            $query = DB::table('health_events')
                ->whereBetween('event_timestamp', [$hourStart, $hourEnd])
                ->where('is_test', $showTest);

            if (!empty($appIdentifiers)) {
                $query->whereIn('app_identifier', $appIdentifiers);
            }

            $counts = $query->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            $trends[] = [
                'hour' => $hourStart->format('H:i'),
                'crashes' => $counts['crash'] ?? 0,
                'errors' => $counts['error'] ?? 0,
                'warnings' => $counts['warning'] ?? 0,
            ];
        }

        return $trends;
    }

    /**
     * Get top error messages
     */
    private function getTopErrors(Carbon $since, int $limit = 10, array $appIdentifiers = [], bool $showTest = false): array
    {
        // $since is in UTC (for event_timestamp), compute local equivalent for created_at
        $sinceLocal = $since->copy()->setTimezone(config('app.timezone'));

        $query = DB::table('health_events')
            ->where(function ($q) use ($since, $sinceLocal) {
                $q->where('event_timestamp', '>=', $since)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereIn('level', ['crash', 'error'])
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        return $query->selectRaw('message, level, app_identifier, COUNT(*) as count')
            ->groupBy('message', 'level', 'app_identifier')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($error) {
                return [
                    'message' => strlen($error->message) > 100
                        ? substr($error->message, 0, 100) . '...'
                        : $error->message,
                    'full_message' => $error->message,
                    'level' => $error->level,
                    'app' => $error->app_identifier,
                    'count' => $error->count,
                ];
            })
            ->toArray();
    }

    /**
     * Display events detail page filtered by app and/or level
     */
    public function events(Request $request)
    {
        $hours = $request->input('hours', 24);
        $sinceUtc = now('UTC')->subHours($hours);  // For event_timestamp (stored in UTC)
        $sinceLocal = now()->subHours($hours);      // For created_at (stored in local time)
        $app = $request->input('app');
        $level = $request->input('level');
        $product = $request->input('product');
        $view = $request->input('view', 'events');
        $showTest = $this->getShowTest($request);
        $showDismissed = $request->boolean('show_dismissed');

        // Get accessible apps for this user
        $accessibleApps = array_keys($this->getApps());

        $query = DB::table('health_events')
            ->where(function ($q) use ($sinceUtc, $sinceLocal) {
                // Show events that either occurred recently OR were uploaded recently
                // This ensures delayed crash reports (e.g., uploaded days after occurring) still appear
                // Note: event_timestamp is UTC, created_at is local time
                $q->where('event_timestamp', '>=', $sinceUtc)
                  ->orWhere('created_at', '>=', $sinceLocal);
            })
            ->whereIn('app_identifier', $accessibleApps) // Always filter by accessible apps
            ->where('is_test', $showTest);

        // Filter dismissed events (hide by default)
        if (!$showDismissed) {
            $query->whereNull('dismissed_at');
        }

        // Filter by product (get all apps for that product)
        if ($product && $product !== 'all') {
            $productApps = array_keys($this->getApps($product));
            $query->whereIn('app_identifier', $productApps);
        }

        // Filter by specific app
        if ($app && $app !== 'all') {
            $query->where('app_identifier', $app);
        }

        // Filter by level
        if ($level && $level !== 'all') {
            $query->where('level', $level);
        }

        // Build title based on filters
        $title = $this->buildTitle($app, $level, $view, $product);

        if ($view === 'devices') {
            // Get unique devices with their latest info (no duplicates for version changes)
            // Use a subquery to find the latest event for each device_id, then get full info
            $latestEventsSubquery = $query->clone()
                ->selectRaw('device_id, MAX(id) as latest_id, MAX(event_timestamp) as last_seen, COUNT(*) as event_count')
                ->groupBy('device_id');

            $devices = DB::table('health_events')
                ->joinSub($latestEventsSubquery, 'latest', function ($join) {
                    $join->on('health_events.id', '=', 'latest.latest_id');
                })
                ->select('health_events.device_id', 'health_events.device_model', 'health_events.platform',
                         'health_events.os_version', 'health_events.app_version',
                         'latest.last_seen', 'latest.event_count')
                ->orderByDesc('latest.last_seen')
                ->paginate(50);

            return view('admin.analytics.devices', [
                'devices' => $devices,
                'title' => $title,
                'hours' => $hours,
                'app' => $app,
                'level' => $level,
                'product' => $product,
                'products' => $this->getProducts(),
                'apps' => $this->getApps(),
                'showTest' => $showTest,
                'showDismissed' => $showDismissed,
            ]);
        }

        $events = $query
            ->orderBy('event_timestamp', 'desc')
            ->paginate(50);

        return view('admin.analytics.events', [
            'events' => $events,
            'title' => $title,
            'hours' => $hours,
            'app' => $app,
            'level' => $level,
            'product' => $product,
            'products' => $this->getProducts(),
            'apps' => $this->getApps(),
            'showTest' => $showTest,
            'showDismissed' => $showDismissed,
        ]);
    }

    /**
     * Display single event detail
     */
    public function show(Request $request, int $id)
    {
        // Get accessible apps for this user
        $accessibleApps = array_keys($this->getApps());

        $event = DB::table('health_events')
            ->where('id', $id)
            ->whereIn('app_identifier', $accessibleApps) // Only allow viewing events for accessible apps
            ->first();

        if (!$event) {
            abort(404);
        }

        return view('admin.analytics.show', [
            'event' => $event,
            'apps' => $this->getApps(),
            'products' => $this->getProducts(),
            'showTest' => $this->getShowTest($request),
        ]);
    }

    /**
     * Build page title based on filters
     */
    private function buildTitle(?string $app, ?string $level, string $view, ?string $product = null): string
    {
        $apps = $this->getApps();
        $products = $this->getProducts();
        $parts = [];

        if ($product && $product !== 'all' && isset($products[$product])) {
            $parts[] = $products[$product]['name'];
        } elseif ($app && $app !== 'all' && isset($apps[$app])) {
            $parts[] = $apps[$app]['name'];
        } else {
            $parts[] = 'All Products';
        }

        if ($view === 'devices') {
            $parts[] = 'Active Devices';
        } elseif ($level && $level !== 'all') {
            $levelNames = [
                'crash' => 'Crashes',
                'error' => 'Errors',
                'warning' => 'Warnings',
                'networkError' => 'Network Errors',
                'info' => 'Info Events',
                'heartbeat' => 'Heartbeats',
            ];
            $parts[] = $levelNames[$level] ?? ucfirst($level);
        } else {
            $parts[] = 'All Events';
        }

        return implode(' - ', $parts);
    }

    /**
     * Display analytics events list
     */
    public function analyticsEvents(Request $request)
    {
        $hours = $request->input('hours', 24);
        $since = now('UTC')->subHours($hours);  // Use UTC for event_timestamp
        $app = $request->input('app');
        $product = $request->input('product');
        $category = $request->input('category');
        $eventName = $request->input('event_name');
        $showTest = $this->getShowTest($request);

        $accessibleApps = array_keys($this->getApps());

        $query = DB::table('analytics_events')
            ->where('event_timestamp', '>=', $since)
            ->whereIn('app_identifier', $accessibleApps)
            ->where('is_test', $showTest);

        // Filter by product
        if ($product && $product !== 'all') {
            $productApps = array_keys($this->getApps($product));
            $query->whereIn('app_identifier', $productApps);
        }

        // Filter by specific app
        if ($app && $app !== 'all') {
            $query->where('app_identifier', $app);
        }

        // Filter by category
        if ($category && $category !== 'all') {
            $query->where('event_category', $category);
        }

        // Filter by event name
        if ($eventName) {
            $query->where('event_name', 'like', "%{$eventName}%");
        }

        // Build title
        $title = $this->buildAnalyticsEventsTitle($app, $product, $category);

        $events = $query
            ->orderBy('event_timestamp', 'desc')
            ->paginate(50);

        // Get unique categories for filter
        $categories = DB::table('analytics_events')
            ->whereIn('app_identifier', $accessibleApps)
            ->where('is_test', $showTest)
            ->distinct()
            ->pluck('event_category')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        return view('admin.analytics.analytics-events', [
            'events' => $events,
            'title' => $title,
            'hours' => $hours,
            'app' => $app,
            'product' => $product,
            'category' => $category,
            'eventName' => $eventName,
            'products' => $this->getProducts(),
            'apps' => $this->getApps(),
            'categories' => $categories,
            'showTest' => $showTest,
        ]);
    }

    /**
     * Build title for analytics events page
     */
    private function buildAnalyticsEventsTitle(?string $app, ?string $product, ?string $category): string
    {
        $apps = $this->getApps();
        $products = $this->getProducts();
        $parts = [];

        if ($product && $product !== 'all' && isset($products[$product])) {
            $parts[] = $products[$product]['name'];
        } elseif ($app && $app !== 'all' && isset($apps[$app])) {
            $parts[] = $apps[$app]['name'];
        } else {
            $parts[] = 'All Products';
        }

        if ($category && $category !== 'all') {
            $parts[] = ucfirst($category) . ' Events';
        } else {
            $parts[] = 'Analytics Events';
        }

        return implode(' - ', $parts);
    }

    /**
     * Dismiss selected events to reset health score (marks as dismissed, doesn't delete)
     */
    public function dismissEvents(Request $request)
    {
        $request->validate([
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'required|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $eventIds = $request->input('event_ids');
        $note = $request->input('note');

        // Get accessible apps for this user
        $accessibleApps = array_keys($this->getApps());

        // Only dismiss events that:
        // 1. Match the provided IDs
        // 2. Belong to apps the user has access to
        // 3. Are not already dismissed
        $count = DB::table('health_events')
            ->whereIn('id', $eventIds)
            ->whereIn('app_identifier', $accessibleApps)
            ->whereNull('dismissed_at')
            ->update([
                'dismissed_at' => now(),
                'dismissed_by' => auth()->id(),
                'dismissed_note' => $note,
            ]);

        return back()->with('success', "Dismissed {$count} event(s). Health score will reset.");
    }

    /**
     * Display the geo map showing session or event locations
     */
    public function geoMap(Request $request)
    {
        $hours = $request->input('hours', 24);
        $product = $request->input('product');
        $viewMode = $request->input('view', 'sessions'); // 'sessions' or 'events'
        $showTest = $this->getShowTest($request);
        $since = now('UTC')->subHours($hours);  // Use UTC for event_timestamp/started_at
        $apps = $this->getApps($product);
        $appIdentifiers = array_keys($apps);

        $markers = collect();
        $locationStats = collect();
        $totalWithGeo = 0;

        if ($viewMode === 'events') {
            // Get events with geo data (aggregated by location for performance)
            $query = DB::table('analytics_events')
                ->where('event_timestamp', '>=', $since)
                ->where('is_test', $showTest)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if (!empty($appIdentifiers)) {
                $query->whereIn('app_identifier', $appIdentifiers);
            }

            // Aggregate events by location (city/region/country) for better performance
            $aggregated = $query
                ->select([
                    'city',
                    'region',
                    'country',
                    'latitude',
                    'longitude',
                    'app_identifier',
                ])
                ->selectRaw('COUNT(*) as event_count')
                ->selectRaw('COUNT(DISTINCT session_id) as session_count')
                ->selectRaw('COUNT(DISTINCT CASE WHEN device_id IS NOT NULL THEN device_id END) as device_count')
                ->groupBy('city', 'region', 'country', 'latitude', 'longitude', 'app_identifier')
                ->orderByDesc('event_count')
                ->limit(500)
                ->get();

            $markers = $aggregated->map(function ($e) use ($apps) {
                return [
                    'type' => 'event',
                    'app_identifier' => $e->app_identifier,
                    'app_name' => $apps[$e->app_identifier]['name'] ?? $e->app_identifier,
                    'location' => $e->city
                        ? ($e->city . ($e->region ? ", {$e->region}" : '') . ($e->country ? ", {$e->country}" : ''))
                        : ($e->country ?? 'Unknown'),
                    'lat' => (float) $e->latitude,
                    'lng' => (float) $e->longitude,
                    'event_count' => $e->event_count,
                    'session_count' => $e->session_count,
                    'device_count' => $e->device_count,
                ];
            });

            $totalWithGeo = $aggregated->sum('event_count');

            // Get location summary for stats (from events)
            $locationStats = DB::table('analytics_events')
                ->where('event_timestamp', '>=', $since)
                ->where('is_test', $showTest)
                ->whereNotNull('country')
                ->when(!empty($appIdentifiers), fn($q) => $q->whereIn('app_identifier', $appIdentifiers))
                ->select('country')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        } else {
            // Get sessions with geo data (original behavior)
            $query = DB::table('analytics_sessions')
                ->where('started_at', '>=', $since)
                ->where('is_test', $showTest)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if (!empty($appIdentifiers)) {
                $query->whereIn('app_identifier', $appIdentifiers);
            }

            $sessions = $query
                ->select([
                    'session_id',
                    'app_identifier',
                    'city',
                    'region',
                    'country',
                    'latitude',
                    'longitude',
                    'started_at',
                    'event_count',
                    'screens_viewed',
                    'platform',
                ])
                ->orderByDesc('started_at')
                ->limit(1000)
                ->get();

            $markers = $sessions->map(function ($s) use ($apps) {
                return [
                    'type' => 'session',
                    'session_id' => $s->session_id,
                    'app_identifier' => $s->app_identifier,
                    'app_name' => $apps[$s->app_identifier]['name'] ?? $s->app_identifier,
                    'location' => $s->city
                        ? ($s->city . ($s->region ? ", {$s->region}" : '') . ($s->country ? ", {$s->country}" : ''))
                        : ($s->country ?? 'Unknown'),
                    'lat' => (float) $s->latitude,
                    'lng' => (float) $s->longitude,
                    'started_at' => Carbon::parse($s->started_at)->format('M d, Y g:i A'),
                    'event_count' => $s->event_count,
                    'screens_viewed' => $s->screens_viewed,
                    'platform' => $s->platform,
                ];
            });

            $totalWithGeo = $markers->count();

            // Get location summary for stats (from sessions)
            $locationStats = DB::table('analytics_sessions')
                ->where('started_at', '>=', $since)
                ->where('is_test', $showTest)
                ->whereNotNull('country')
                ->when(!empty($appIdentifiers), fn($q) => $q->whereIn('app_identifier', $appIdentifiers))
                ->select('country')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        }

        return view('admin.analytics.geo-map', [
            'markers' => $markers,
            'locationStats' => $locationStats,
            'totalWithGeo' => $totalWithGeo,
            'hours' => $hours,
            'product' => $product,
            'products' => $this->getProducts(),
            'showTest' => $showTest,
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Display AI-generated summary reports
     */
    public function summaries(Request $request)
    {
        $product = $request->input('product');
        $type = $request->input('type', 'all'); // health, analytics, or all
        $products = $this->getProducts();

        // Get accessible product IDs
        $accessibleProducts = array_keys($products);
        $productIds = Product::whereIn('slug', $accessibleProducts)->pluck('id', 'slug');

        // Filter by product if specified
        $filteredProductIds = $product
            ? [$productIds[$product] ?? null]
            : $productIds->values()->toArray();

        $filteredProductIds = array_filter($filteredProductIds);

        // Build query
        $query = AiSummary::whereIn('product_id', $filteredProductIds)
            ->recent()
            ->orderByDesc('generated_at');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Get summaries with pagination
        $summaries = $query->with('product')->paginate(20);

        // Get latest summary for each type
        $latestHealth = AiSummary::whereIn('product_id', $filteredProductIds)
            ->health()
            ->orderByDesc('generated_at')
            ->first();

        $latestAnalytics = AiSummary::whereIn('product_id', $filteredProductIds)
            ->analytics()
            ->orderByDesc('generated_at')
            ->first();

        // Summary counts
        $healthCount = AiSummary::whereIn('product_id', $filteredProductIds)->recent()->health()->count();
        $analyticsCount = AiSummary::whereIn('product_id', $filteredProductIds)->recent()->analytics()->count();

        return view('admin.analytics.summaries', [
            'summaries' => $summaries,
            'latestHealth' => $latestHealth,
            'latestAnalytics' => $latestAnalytics,
            'healthCount' => $healthCount,
            'analyticsCount' => $analyticsCount,
            'product' => $product,
            'type' => $type,
            'products' => $products,
        ]);
    }

    /**
     * Display metrics dashboard (AI tokens, API calls, etc.)
     */
    public function metrics(Request $request)
    {
        $days = min($request->input('days', 7), 90);
        $product = $request->input('product');
        $showTest = $this->getShowTest($request);
        $metricName = $request->input('metric', 'ai_tokens');
        $products = $this->getProducts();
        $apps = $this->getApps($product);
        $appIdentifiers = array_keys($apps);

        $startDate = now()->subDays($days)->startOfDay();

        // Get daily aggregates for selected metric
        $dailyQuery = DB::table('app_metrics_aggregated')
            ->where('name', $metricName)
            ->where('period_type', 'daily')
            ->where('period_start', '>=', $startDate)
            ->where('is_test', $showTest);

        if (!empty($appIdentifiers)) {
            $dailyQuery->whereIn('app_identifier', $appIdentifiers);
        }

        $dailyData = $dailyQuery->orderBy('period_start')->get();

        // Calculate totals
        $totals = [
            'count' => $dailyData->sum('count'),
            'total_input_tokens' => $dailyData->sum('total_input_tokens'),
            'total_output_tokens' => $dailyData->sum('total_output_tokens'),
            'total_tokens' => $dailyData->sum('total_tokens'),
            'total_cost_cents' => $dailyData->sum('total_cost_cents'),
        ];

        // Group daily data by date for charting
        $chartData = $dailyData->groupBy(fn($d) => Carbon::parse($d->period_start)->format('Y-m-d'))
            ->map(function ($group) {
                return [
                    'count' => $group->sum('count'),
                    'input_tokens' => $group->sum('total_input_tokens'),
                    'output_tokens' => $group->sum('total_output_tokens'),
                    'total_tokens' => $group->sum('total_tokens'),
                    'cost_cents' => $group->sum('total_cost_cents'),
                ];
            });

        // Get breakdown by app
        $appBreakdown = DB::table('app_metrics_aggregated')
            ->where('name', $metricName)
            ->where('period_type', 'daily')
            ->where('period_start', '>=', $startDate)
            ->where('is_test', $showTest)
            ->when(!empty($appIdentifiers), fn($q) => $q->whereIn('app_identifier', $appIdentifiers))
            ->select('app_identifier')
            ->selectRaw('SUM(count) as total_count')
            ->selectRaw('SUM(total_input_tokens) as total_input')
            ->selectRaw('SUM(total_output_tokens) as total_output')
            ->selectRaw('SUM(total_tokens) as total_tokens')
            ->selectRaw('SUM(total_cost_cents) as total_cost')
            ->groupBy('app_identifier')
            ->orderByDesc('total_tokens')
            ->get();

        // Get recent raw metrics
        $recentMetrics = DB::table('app_metrics')
            ->where('name', $metricName)
            ->where('metric_timestamp', '>=', $startDate)
            ->where('is_test', $showTest)
            ->when(!empty($appIdentifiers), fn($q) => $q->whereIn('app_identifier', $appIdentifiers))
            ->orderByDesc('metric_timestamp')
            ->limit(20)
            ->get();

        // Get available metric types
        $metricTypes = DB::table('app_metrics')
            ->when(!empty($appIdentifiers), fn($q) => $q->whereIn('app_identifier', $appIdentifiers))
            ->distinct()
            ->pluck('name')
            ->toArray();

        return view('admin.analytics.metrics', [
            'totals' => $totals,
            'chartData' => $chartData,
            'appBreakdown' => $appBreakdown,
            'recentMetrics' => $recentMetrics,
            'metricTypes' => $metricTypes,
            'days' => $days,
            'product' => $product,
            'products' => $products,
            'apps' => $apps,
            'showTest' => $showTest,
            'metricName' => $metricName,
        ]);
    }
}
