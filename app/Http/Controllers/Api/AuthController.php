<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    /**
     * Get API key for an app identifier using app secret
     */
    public function getKey(Request $request, string $appIdentifier)
    {
        // Check rate limit (100 requests per hour per IP)
        $rateLimitKey = 'auth_key_limit:' . $request->ip();
        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts >= 100) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded. Try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        // Increment rate limit counter
        Cache::put($rateLimitKey, $attempts + 1, now()->addHour());

        // Validate app secret header
        $appSecret = $request->header('X-App-Secret');

        if (empty($appSecret)) {
            return response()->json([
                'success' => false,
                'error' => 'Missing X-App-Secret header',
                'code' => 'AUTH_MISSING_SECRET',
            ], 401);
        }

        // Validate the secret
        if (!AppSecret::validateForApp($appIdentifier, $appSecret)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid app secret',
                'code' => 'AUTH_INVALID_SECRET',
            ], 401);
        }

        // Get the API key for this app
        $apiKey = AppSecret::getApiKeyForApp($appIdentifier);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'App identifier not found',
                'code' => 'APP_NOT_FOUND',
            ], 404);
        }

        // Return the API key with 24-hour expiry
        return response()->json([
            'success' => true,
            'apiKey' => $apiKey,
            'expiresAt' => now()->addHours(24)->toIso8601String(),
        ]);
    }
}
