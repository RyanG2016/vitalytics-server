<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppMetric;
use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MetricsController extends Controller
{
    /**
     * Receive metrics from client SDKs
     *
     * POST /api/v1/metrics
     */
    public function store(Request $request): JsonResponse
    {
        // Validate API key
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

        // Check API key
        if (!$app->validateApiKey($apiKey)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_api_key',
                'message' => 'Invalid API key',
            ], 401);
        }

        // Rate limiting: 500 metrics per minute per app
        $rateLimitKey = "metrics:{$appIdentifier}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 500)) {
            return response()->json([
                'success' => false,
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Validate payload
        $validator = Validator::make($request->all(), [
            'appIdentifier' => 'required|string|max:100',
            'deviceId' => 'nullable|string|max:100',
            'metric' => 'required|array',
            'metric.id' => 'required|string|max:100',
            'metric.name' => 'required|string|max:100',
            'metric.data' => 'required|array',
            'metric.aggregate' => 'nullable|string|in:sum,avg,min,max,count',
            'metric.tags' => 'nullable|array',
            'metric.user_id' => 'nullable|string|max:100',
            'metric.timestamp' => 'nullable|string',
            'isTest' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $metric = $validated['metric'];

        // Check for duplicate metric_id
        if (AppMetric::where('metric_id', $metric['id'])->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Metric already recorded',
                'metricId' => $metric['id'],
            ]);
        }

        try {
            $metricTimestamp = isset($metric['timestamp'])
                ? Carbon::parse($metric['timestamp'])
                : Carbon::now('UTC');
        } catch (\Exception $e) {
            $metricTimestamp = Carbon::now('UTC');
        }

        // Store the metric
        $appMetric = AppMetric::create([
            'metric_id' => $metric['id'],
            'app_identifier' => $appIdentifier,
            'device_id' => $validated['deviceId'] ?? null,
            'name' => $metric['name'],
            'data' => $metric['data'],
            'aggregate_type' => $metric['aggregate'] ?? 'sum',
            'tags' => $metric['tags'] ?? [],
            'user_id' => $metric['user_id'] ?? null,
            'is_test' => $validated['isTest'] ?? false,
            'metric_timestamp' => $metricTimestamp,
            'received_at' => Carbon::now('UTC'),
        ]);

        // Update aggregated metrics (for AI tokens)
        if ($metric['name'] === 'ai_tokens') {
            $this->updateAiTokenAggregates($appMetric);
        }

        Log::debug("MetricsController: Stored metric {$metric['name']} for {$appIdentifier}");

        return response()->json([
            'success' => true,
            'metricId' => $metric['id'],
        ]);
    }

    /**
     * Update aggregated AI token metrics
     */
    private function updateAiTokenAggregates(AppMetric $metric): void
    {
        $data = $metric->data;
        $hourStart = $metric->metric_timestamp->copy()->startOfHour();

        // Update or create hourly aggregate
        DB::table('app_metrics_aggregated')->updateOrInsert(
            [
                'app_identifier' => $metric->app_identifier,
                'name' => 'ai_tokens',
                'period_type' => 'hourly',
                'period_start' => $hourStart,
                'is_test' => $metric->is_test,
            ],
            [
                'period_end' => $hourStart->copy()->endOfHour(),
                'count' => DB::raw('count + 1'),
                'total_input_tokens' => DB::raw('total_input_tokens + ' . (int)($data['input_tokens'] ?? 0)),
                'total_output_tokens' => DB::raw('total_output_tokens + ' . (int)($data['output_tokens'] ?? 0)),
                'total_tokens' => DB::raw('total_tokens + ' . (int)($data['total_tokens'] ?? 0)),
                'total_cost_cents' => DB::raw('total_cost_cents + ' . (float)($data['cost_cents'] ?? 0)),
                'updated_at' => now(),
            ]
        );

        // Update daily aggregate
        $dayStart = $metric->metric_timestamp->copy()->startOfDay();

        DB::table('app_metrics_aggregated')->updateOrInsert(
            [
                'app_identifier' => $metric->app_identifier,
                'name' => 'ai_tokens',
                'period_type' => 'daily',
                'period_start' => $dayStart,
                'is_test' => $metric->is_test,
            ],
            [
                'period_end' => $dayStart->copy()->endOfDay(),
                'count' => DB::raw('count + 1'),
                'total_input_tokens' => DB::raw('total_input_tokens + ' . (int)($data['input_tokens'] ?? 0)),
                'total_output_tokens' => DB::raw('total_output_tokens + ' . (int)($data['output_tokens'] ?? 0)),
                'total_tokens' => DB::raw('total_tokens + ' . (int)($data['total_tokens'] ?? 0)),
                'total_cost_cents' => DB::raw('total_cost_cents + ' . (float)($data['cost_cents'] ?? 0)),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get metrics summary for an app
     *
     * GET /api/v1/metrics/summary/{appIdentifier}
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
        $metricName = $request->input('metric', 'ai_tokens');

        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // Get daily aggregates
        $dailyData = DB::table('app_metrics_aggregated')
            ->where('app_identifier', $appIdentifier)
            ->where('name', $metricName)
            ->where('period_type', 'daily')
            ->where('period_start', '>=', $startDate)
            ->where('is_test', $isTest)
            ->orderBy('period_start')
            ->get();

        // Calculate totals
        $totals = [
            'count' => $dailyData->sum('count'),
            'total_input_tokens' => $dailyData->sum('total_input_tokens'),
            'total_output_tokens' => $dailyData->sum('total_output_tokens'),
            'total_tokens' => $dailyData->sum('total_tokens'),
            'total_cost_cents' => $dailyData->sum('total_cost_cents'),
        ];

        return response()->json([
            'success' => true,
            'period' => [
                'days' => $days,
                'start' => $startDate->toIso8601String(),
                'end' => Carbon::now()->toIso8601String(),
            ],
            'metric' => $metricName,
            'totals' => $totals,
            'daily' => $dailyData->map(fn($d) => [
                'date' => Carbon::parse($d->period_start)->format('Y-m-d'),
                'count' => $d->count,
                'input_tokens' => $d->total_input_tokens,
                'output_tokens' => $d->total_output_tokens,
                'total_tokens' => $d->total_tokens,
                'cost_cents' => $d->total_cost_cents,
            ]),
        ]);
    }
}
