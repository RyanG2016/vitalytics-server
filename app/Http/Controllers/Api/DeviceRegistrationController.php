<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationToken;
use App\Models\DeviceApiKey;
use App\Models\DeviceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeviceRegistrationController extends Controller
{
    /**
     * Rate limit settings
     */
    const RATE_LIMIT_PER_IP = 10;        // Requests per IP per minute
    const RATE_LIMIT_WINDOW = 60;         // Seconds
    const MAX_FAILURES_PER_TOKEN = 3;     // Failures before token is invalidated
    const GLOBAL_RATE_LIMIT = 100;        // Global requests per minute

    /**
     * Register a device and obtain an API key
     *
     * POST /api/v1/devices/register
     */
    public function register(Request $request): JsonResponse
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check rate limits
        $rateLimitResult = $this->checkRateLimits($ipAddress, $userAgent);
        if ($rateLimitResult !== true) {
            return $rateLimitResult;
        }

        // Validate request
        $validated = $request->validate([
            'registration_token' => 'required|string|min:40|max:50',
            'device_id' => 'required|string|min:8|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_hostname' => 'nullable|string|max:255',
            'device_os' => 'nullable|string|max:100',
        ]);

        // Validate token format
        if (!str_starts_with($validated['registration_token'], RegistrationToken::TOKEN_PREFIX)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_token_format',
                'message' => 'Registration token format is invalid.',
            ], 400);
        }

        // Find and validate token
        $token = RegistrationToken::findAndValidate($validated['registration_token']);

        if (!$token) {
            // Log failed attempt
            $this->trackTokenFailure($validated['registration_token'], $ipAddress);

            DeviceAuditLog::logRegistrationFailed(
                'unknown',
                'Token not found or invalid',
                $validated['device_id'],
                null,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Registration token is invalid.',
            ], 401);
        }

        // Check token validity
        if ($token->isRevoked()) {
            DeviceAuditLog::logRegistrationFailed(
                $token->app_identifier,
                'Token revoked',
                $validated['device_id'],
                $token->id,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => false,
                'error' => 'token_revoked',
                'message' => 'Registration token has been revoked.',
            ], 401);
        }

        if ($token->isExpired()) {
            DeviceAuditLog::logRegistrationFailed(
                $token->app_identifier,
                'Token expired',
                $validated['device_id'],
                $token->id,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => false,
                'error' => 'token_expired',
                'message' => 'Registration token has expired.',
            ], 401);
        }

        if ($token->isExhausted()) {
            DeviceAuditLog::logRegistrationFailed(
                $token->app_identifier,
                'Token exhausted',
                $validated['device_id'],
                $token->id,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => false,
                'error' => 'token_exhausted',
                'message' => 'Registration token has reached its maximum uses.',
            ], 401);
        }

        // Check if device is already registered
        if (DeviceApiKey::isDeviceRegistered($token->app_identifier, $validated['device_id'])) {
            DeviceAuditLog::create([
                'event_type' => DeviceAuditLog::EVENT_DEVICE_ALREADY_REGISTERED,
                'app_identifier' => $token->app_identifier,
                'device_id' => $validated['device_id'],
                'registration_token_id' => $token->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'device_already_registered',
                'message' => 'This device is already registered. Contact administrator if you need a new API key.',
            ], 409);
        }

        // Perform registration in a transaction
        try {
            $result = DB::transaction(function () use ($token, $validated, $ipAddress, $userAgent) {
                // Generate device API key
                $keyResult = DeviceApiKey::generate(
                    $token->app_identifier,
                    $validated['device_id'],
                    $token->id,
                    $ipAddress,
                    $validated['device_name'] ?? null,
                    $validated['device_hostname'] ?? null,
                    $validated['device_os'] ?? null
                );

                // Increment token uses
                $token->incrementUses();

                // Log successful registration
                DeviceAuditLog::logDeviceRegistered(
                    $keyResult['model'],
                    $token,
                    $ipAddress,
                    $userAgent
                );

                // Log token usage
                DeviceAuditLog::create([
                    'event_type' => DeviceAuditLog::EVENT_TOKEN_USED,
                    'app_identifier' => $token->app_identifier,
                    'device_id' => $validated['device_id'],
                    'registration_token_id' => $token->id,
                    'device_api_key_id' => $keyResult['model']->id,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'details' => [
                        'uses_after' => $token->fresh()->uses_count,
                    ],
                ]);

                return $keyResult;
            });

            Log::info('Device registered successfully', [
                'app' => $token->app_identifier,
                'device_id' => $validated['device_id'],
                'ip' => $ipAddress,
            ]);

            return response()->json([
                'success' => true,
                'api_key' => $result['key'],
                'device_id' => $validated['device_id'],
                'app_identifier' => $token->app_identifier,
                'message' => 'Device registered successfully. Store this API key securely - it will not be shown again.',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Device registration failed', [
                'error' => $e->getMessage(),
                'app' => $token->app_identifier,
                'device_id' => $validated['device_id'],
            ]);

            DeviceAuditLog::logRegistrationFailed(
                $token->app_identifier,
                'Internal error: ' . $e->getMessage(),
                $validated['device_id'],
                $token->id,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => false,
                'error' => 'registration_failed',
                'message' => 'Registration failed due to an internal error. Please try again.',
            ], 500);
        }
    }

    /**
     * Check rate limits
     */
    protected function checkRateLimits(string $ipAddress, ?string $userAgent): JsonResponse|bool
    {
        // Per-IP rate limit
        $ipKey = 'device_reg_ip:' . $ipAddress;
        $ipAttempts = Cache::get($ipKey, 0);

        if ($ipAttempts >= self::RATE_LIMIT_PER_IP) {
            DeviceAuditLog::logRateLimited($ipAddress, null, $userAgent);

            return response()->json([
                'success' => false,
                'error' => 'rate_limited',
                'message' => 'Too many registration attempts. Please try again later.',
            ], 429);
        }

        Cache::put($ipKey, $ipAttempts + 1, self::RATE_LIMIT_WINDOW);

        // Global rate limit
        $globalKey = 'device_reg_global';
        $globalAttempts = Cache::get($globalKey, 0);

        if ($globalAttempts >= self::GLOBAL_RATE_LIMIT) {
            return response()->json([
                'success' => false,
                'error' => 'rate_limited',
                'message' => 'Service is busy. Please try again later.',
            ], 429);
        }

        Cache::put($globalKey, $globalAttempts + 1, self::RATE_LIMIT_WINDOW);

        return true;
    }

    /**
     * Track token failures for auto-invalidation
     */
    protected function trackTokenFailure(string $providedToken, string $ipAddress): void
    {
        // Get the prefix if we can
        $prefix = substr($providedToken, 0, 12);
        $failKey = 'token_failures:' . $prefix;

        $failures = Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $failures, 3600); // Track for 1 hour

        // Auto-invalidate token after too many failures
        if ($failures >= self::MAX_FAILURES_PER_TOKEN) {
            $token = RegistrationToken::byPrefix($prefix)->first();
            if ($token && !$token->is_revoked) {
                $token->revoke();

                DeviceAuditLog::create([
                    'event_type' => DeviceAuditLog::EVENT_TOKEN_VALIDATION_FAILED,
                    'app_identifier' => $token->app_identifier,
                    'registration_token_id' => $token->id,
                    'ip_address' => $ipAddress,
                    'details' => [
                        'reason' => 'Auto-revoked after ' . self::MAX_FAILURES_PER_TOKEN . ' failed attempts',
                        'failure_count' => $failures,
                    ],
                ]);

                Log::warning('Token auto-revoked due to failed attempts', [
                    'token_id' => $token->id,
                    'failures' => $failures,
                    'ip' => $ipAddress,
                ]);
            }
        }
    }

    /**
     * Validate a device API key (for testing/health check)
     *
     * POST /api/v1/devices/validate
     */
    public function validateKey(Request $request): JsonResponse
    {
        $apiKey = $request->header('X-Device-Key') ?? $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'error' => 'No API key provided',
            ], 401);
        }

        $deviceKey = DeviceApiKey::findAndValidate($apiKey);

        if (!$deviceKey) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'error' => 'Invalid API key',
            ], 401);
        }

        if ($deviceKey->isRevoked()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'error' => 'API key has been revoked',
            ], 401);
        }

        // Update last used
        $deviceKey->touchLastUsed($request->ip());

        return response()->json([
            'success' => true,
            'valid' => true,
            'device_id' => $deviceKey->device_id,
            'app_identifier' => $deviceKey->app_identifier,
        ]);
    }
}
