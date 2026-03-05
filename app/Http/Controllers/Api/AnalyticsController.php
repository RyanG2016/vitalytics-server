<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsDailyStat;
use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Receive analytics events from client SDKs
     *
     * POST /api/v1/analytics/events
     */
    public function store(Request $request): JsonResponse
    {
        // Validate API key (same as health events)
        $apiKey = $request->header('X-API-Key');
        $appIdentifier = $request->input('appIdentifier');

        if (!$apiKey || !$appIdentifier) {
            return response()->json([
                'success' => false,
                'error' => 'missing_credentials',
                'message' => 'API key and app identifier required',
            ], 401);
        }

        // Verify the API key matches the app
        $app = App::where('identifier', $appIdentifier)->first();

        if (!$app) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_app',
                'message' => 'Unknown app identifier',
            ], 401);
        }

        // Check API key (using same system as health events)
        if (!$app->validateApiKey($apiKey)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_api_key',
                'message' => 'Invalid API key',
            ], 401);
        }

        // Rate limiting: 1000 events per minute per app
        $rateLimitKey = "analytics:{$appIdentifier}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1000)) {
            return response()->json([
                'success' => false,
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Validate payload (use Validator to avoid redirect on failure)
        Log::debug('Analytics payload received', ['payload' => $request->all()]);
        $validator = Validator::make($request->all(), [
            'batchId' => 'required|string|max:100',
            'appIdentifier' => 'required|string|max:100',
            'deviceInfo' => 'required|array',
            'deviceInfo.deviceId' => 'nullable|string|max:100', // Optional for anonymous analytics
            'deviceInfo.deviceModel' => 'nullable|string|max:100',
            'deviceInfo.platform' => 'required|string|max:20',
            'deviceInfo.osVersion' => 'nullable|string|max:255',
            'deviceInfo.appVersion' => 'nullable|string|max:50',
            'deviceInfo.screenResolution' => 'nullable|string|max:20',
            'deviceInfo.language' => 'nullable|string|max:10',
            'userId' => 'nullable|string|max:255',
            'anonymousUserId' => 'nullable|string|max:100',
            'isTest' => 'nullable|boolean',
            'environment' => 'nullable|string|max:20',
            'sentAt' => 'required|string',
            'events' => 'required|array|max:100',
            'events.*.id' => 'required|string|max:100',
            'events.*.timestamp' => 'required|string',
            'events.*.eventType' => 'required|string|max:100',  // Client sends eventType, not name
            'events.*.sessionId' => 'required|string|max:100',  // Session ID is per-event
            'events.*.category' => 'nullable|string|max:50',    // Category is optional
            'events.*.screen' => 'nullable|string|max:200',
            'events.*.element' => 'nullable|string|max:100',
            'events.*.properties' => 'nullable|array',
            'events.*.duration' => 'nullable|integer|min:0',
            'events.*.referrer' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            Log::debug('Analytics validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Add client IP for geolocation
        $validated['_client_ip'] = $request->ip();
        $validated['_product_id'] = $app->product_id;

        // Queue for async processing
        ProcessAnalyticsEvents::dispatch($validated);

        return response()->json([
            'success' => true,
            'batchId' => $validated['batchId'],
            'eventsReceived' => count($validated['events']),
        ]);
    }

    /**
     * Get analytics summary for an app
     *
     * GET /api/v1/analytics/summary/{appIdentifier}
     */
    public function summary(Request $request, string $appIdentifier): JsonResponse
    {
        // Validate API key
        $apiKey = $request->header('X-API-Key');
        $app = App::where('identifier', $appIdentifier)->first();

        if (!$app || !$apiKey || !$app->validateApiKey($apiKey)) {
            return response()->json(['success' => false, 'error' => 'unauthorized'], 401);
        }

        $days = min($request->input('days', 7), 90);
        $isTest = $request->boolean('test', false);
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get summary stats
        $query = AnalyticsEvent::forApp($appIdentifier)
            ->where('is_test', $isTest)
            ->where('event_timestamp', '>=', $startDate);

        $totalEvents = (clone $query)->count();
        $uniqueSessions = (clone $query)->distinct('session_id')->count('session_id');

        // Separate metrics for identified vs anonymous data
        $identifiedDevices = (clone $query)->whereNotNull('device_id')->distinct('device_id')->count('device_id');
        $anonymousSessions = (clone $query)->whereNull('device_id')->distinct('session_id')->count('session_id');

        // Top events
        $topEvents = (clone $query)
            ->selectRaw('event_name, event_category, COUNT(*) as count')
            ->groupBy('event_name', 'event_category')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Events by category
        $byCategory = (clone $query)
            ->selectRaw('event_category, COUNT(*) as count')
            ->groupBy('event_category')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'success' => true,
            'period' => [
                'days' => $days,
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'summary' => [
                'totalEvents' => $totalEvents,
                'uniqueSessions' => $uniqueSessions,
                'identifiedDevices' => $identifiedDevices,
                'anonymousSessions' => $anonymousSessions,
            ],
            'topEvents' => $topEvents,
            'byCategory' => $byCategory,
        ]);
    }
}
