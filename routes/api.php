<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthEventsController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\DeviceRegistrationController;
use App\Http\Controllers\Api\MobileAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Vitalytics API endpoints for receiving health events from client apps.
| Base URL: https://api.vitalytics.app
|
*/

// Health Events API (v1)
Route::prefix('v1/health')->group(function () {
    // Receive health events from client apps
    // POST /v1/health/events
    Route::post('/events', [HealthEventsController::class, 'store']);
    
    // Get status for an app
    // GET /v1/health/status/{appIdentifier}
    Route::get('/status/{appIdentifier}', [HealthEventsController::class, 'status']);
    
    // Get events list for an app
    // GET /v1/health/events/{appIdentifier}
    Route::get('/events/{appIdentifier}', [HealthEventsController::class, 'index']);
    
    // Get single event detail
    // GET /v1/health/events/{appIdentifier}/{eventId}
    Route::get('/events/{appIdentifier}/{eventId}', [HealthEventsController::class, 'show']);
});

// Health check endpoint
Route::get('/v1/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'vitalytics',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Analytics API (v1)
Route::prefix('v1/analytics')->group(function () {
    // Receive analytics events from client apps
    // POST /v1/analytics/events
    Route::post('/events', [AnalyticsController::class, 'store']);

    // Get analytics summary for an app
    // GET /v1/analytics/summary/{appIdentifier}
    Route::get('/summary/{appIdentifier}', [AnalyticsController::class, 'summary']);
});

// Feedback API (v1)
Route::prefix('v1/feedback')->group(function () {
    // Receive feedback from client apps
    // POST /v1/feedback
    Route::post('/', [FeedbackController::class, 'store']);
});

// Auth API (v1) - Get API keys using app secrets
Route::prefix('v1/auth')->group(function () {
    // Get API key for an app
    // GET /v1/auth/key/{appIdentifier}
    // Header: X-App-Secret
    Route::get('/key/{appIdentifier}', [AuthController::class, 'getKey']);
});

// Mobile Authentication API (v1) - User login/logout for mobile apps
Route::prefix('v1/auth')->group(function () {
    // Login and receive a Sanctum token
    // POST /v1/auth/login
    // Body: { "email": "...", "password": "...", "device_name": "..." }
    Route::post('/login', [MobileAuthController::class, 'login']);

    // Request password reset email
    // POST /v1/auth/forgot-password
    // Body: { "email": "..." }
    Route::post('/forgot-password', [MobileAuthController::class, 'forgotPassword']);

    // Reset password with token (from email link)
    // POST /v1/auth/reset-password
    // Body: { "email": "...", "token": "...", "password": "...", "password_confirmation": "..." }
    Route::post('/reset-password', [MobileAuthController::class, 'resetPassword']);
});

// Protected mobile auth routes (require Sanctum token)
Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function () {
    // Get authenticated user profile
    // GET /v1/auth/user
    Route::get('/user', [MobileAuthController::class, 'user']);

    // Logout (revoke current token)
    // POST /v1/auth/logout
    Route::post('/logout', [MobileAuthController::class, 'logout']);

    // Logout from all devices (revoke all tokens)
    // POST /v1/auth/logout-all
    Route::post('/logout-all', [MobileAuthController::class, 'logoutAll']);

    // Refresh token (get new token, revoke old one)
    // POST /v1/auth/refresh
    Route::post('/refresh', [MobileAuthController::class, 'refresh']);

    // Create a one-time web session token for WebView authentication
    // POST /v1/auth/web-session-token
    // Body: { "redirect": "/analytics" } (optional)
    // Returns URL to open in WebView that will create session cookies
    Route::post('/web-session-token', [MobileAuthController::class, 'createWebSessionToken']);
});

// Device Token API (v1) - Push notification device registration
// Protected by Sanctum authentication
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // List registered devices
    // GET /v1/device-tokens
    Route::get('/device-tokens', [DeviceTokenController::class, 'index']);

    // Register a device token
    // POST /v1/device-tokens
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);

    // Remove a device token
    // DELETE /v1/device-tokens/{token}
    Route::delete('/device-tokens/{token}', [DeviceTokenController::class, 'destroy']);

    // Send test push notification (development only)
    // POST /v1/test-push
    Route::post('/test-push', [DeviceTokenController::class, 'testPush']);
});

// Metrics API (v1)
Route::prefix('v1/metrics')->group(function () {
    // Receive metrics from client apps
    // POST /v1/metrics
    Route::post('/', [MetricsController::class, 'store']);

    // Get metrics summary for an app
    // GET /v1/metrics/summary/{appIdentifier}
    Route::get('/summary/{appIdentifier}', [MetricsController::class, 'summary']);
});

// Config API (v1)
Route::prefix('v1/config')->group(function () {
    // Get config manifest for an app
    // GET /v1/config/{appIdentifier}
    Route::get('/{appIdentifier}', [ConfigController::class, 'show']);

    // Get specific config content
    // GET /v1/config/{appIdentifier}/{configKey}
    Route::get('/{appIdentifier}/{configKey}', [ConfigController::class, 'show']);
});

// Device Registration API (v1)
Route::prefix('v1/devices')->group(function () {
    // Register a device with a registration token and receive an API key
    // POST /v1/devices/register
    Route::post('/register', [DeviceRegistrationController::class, 'register']);

    // Validate a device API key
    // POST /v1/devices/validate
    Route::post('/validate', [DeviceRegistrationController::class, 'validateKey']);
});
