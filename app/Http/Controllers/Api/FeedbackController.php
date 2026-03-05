<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ProductFeedback;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Receive feedback from client SDKs
     *
     * POST /api/v1/feedback
     */
    public function store(Request $request): JsonResponse
    {
        // Validate API key
        $apiKey = $request->header('X-API-Key');
        $appIdentifier = $request->input('appIdentifier') ?? $request->header('X-App-Identifier');

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

        // Rate limiting: 10 feedback per minute per device (prevent spam)
        $deviceId = $request->input('deviceId') ?? $request->ip();
        $rateLimitKey = "feedback:{$appIdentifier}:{$deviceId}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->json([
                'success' => false,
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many feedback submissions. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Validate payload
        $validator = Validator::make($request->all(), [
            'appIdentifier' => 'required|string|max:100',
            'message' => 'required|string|max:10000',
            'category' => 'nullable|string|in:general,bug,feature-request,praise,support',
            'rating' => 'nullable|integer|min:1|max:5',
            'email' => 'nullable|email|max:255',
            'userId' => 'nullable|string|max:255',
            'deviceId' => 'nullable|string|max:100',
            'sessionId' => 'nullable|string|max:100',
            'screen' => 'nullable|string|max:100',
            'deviceInfo' => 'nullable|array',
            'deviceInfo.platform' => 'nullable|string|max:50',
            'deviceInfo.osVersion' => 'nullable|string|max:255',
            'deviceInfo.appVersion' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
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

        $data = $validator->validated();
        $deviceInfo = $data['deviceInfo'] ?? [];

        // Get geolocation from IP
        $geoData = $this->getGeoLocation($request->ip());

        // Create feedback record
        try {
            $feedback = ProductFeedback::create([
                'app_identifier' => $appIdentifier,
                'device_id' => $data['deviceId'] ?? null,
                'session_id' => $data['sessionId'] ?? null,
                'message' => $data['message'],
                'category' => $data['category'] ?? 'general',
                'rating' => $data['rating'] ?? null,
                'email' => $data['email'] ?? null,
                'user_id' => $data['userId'] ?? null,
                'screen' => $data['screen'] ?? null,
                'app_version' => $deviceInfo['appVersion'] ?? null,
                'platform' => $deviceInfo['platform'] ?? null,
                'os_version' => $deviceInfo['osVersion'] ?? null,
                'country' => $geoData['country'] ?? null,
                'city' => $geoData['city'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'is_test' => $data['isTest'] ?? false,
            ]);

            Log::info('Feedback received', [
                'app' => $appIdentifier,
                'category' => $feedback->category,
                'rating' => $feedback->rating,
            ]);

            // Send push notifications to users with access to this product
            $this->sendFeedbackNotifications($app, $feedback);

            return response()->json([
                'success' => true,
                'message' => 'Feedback received successfully',
                'feedbackId' => $feedback->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to store feedback', [
                'error' => $e->getMessage(),
                'app' => $appIdentifier,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Failed to store feedback',
            ], 500);
        }
    }

    /**
     * Get geolocation data from IP address
     */
    private function getGeoLocation(string $ip): array
    {
        // Skip for localhost/private IPs
        if (in_array($ip, ['127.0.0.1', '::1']) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return [];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city");

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    return [
                        'country' => $data['countryCode'] ?? null,
                        'city' => $data['city'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail - geolocation is optional
        }

        return [];
    }

    /**
     * Send push notifications for new feedback to users with product access
     */
    private function sendFeedbackNotifications(App $app, ProductFeedback $feedback): void
    {
        try {
            // Skip notifications for test feedback
            if ($feedback->is_test) {
                return;
            }

            $product = $app->product;
            if (!$product) {
                return;
            }

            $productSlug = $product->slug;
            $pushService = app(PushNotificationService::class);

            // Get all users who can access this product
            // Admins have access to all products (roles use many-to-many via role_user pivot)
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
            $adminUserIds = DB::table('role_user')
                ->where('role_id', $adminRoleId)
                ->pluck('user_id')
                ->toArray();

            // Users specifically assigned to this product
            $assignedUserIds = DB::table('user_products')
                ->where('product_slug', $productSlug)
                ->pluck('user_id')
                ->toArray();

            // Merge and deduplicate
            $userIds = array_unique(array_merge($adminUserIds, $assignedUserIds));

            if (empty($userIds)) {
                return;
            }

            // Build notification content
            $categoryLabels = [
                'bug' => 'Bug Report',
                'feature-request' => 'Feature Request',
                'praise' => 'Praise',
                'support' => 'Support Request',
                'general' => 'Feedback',
            ];
            $categoryLabel = $categoryLabels[$feedback->category] ?? 'Feedback';
            $title = "New {$categoryLabel}: {$product->name}";

            // Truncate message for push notification
            $message = strlen($feedback->message) > 100
                ? substr($feedback->message, 0, 97) . '...'
                : $feedback->message;

            $data = [
                'type' => 'feedback',
                'feedback_id' => $feedback->id,
                'product_slug' => $productSlug,
                'category' => $feedback->category,
                'deep_link' => "vitalytics://feedback/{$feedback->id}",
            ];

            // Send to each user (respects feedback_alerts preference)
            foreach ($userIds as $userId) {
                $pushService->sendFeedbackAlert($userId, $title, $message, $data);
            }

            Log::debug('Feedback notifications sent', [
                'feedback_id' => $feedback->id,
                'user_count' => count($userIds),
            ]);

        } catch (\Exception $e) {
            // Don't fail the request if notifications fail
            Log::error('Failed to send feedback notifications', [
                'error' => $e->getMessage(),
                'feedback_id' => $feedback->id,
            ]);
        }
    }
}
