<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    /**
     * Register a device token for push notifications.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
            'platform' => 'required|in:ios,android',
            'device_name' => 'nullable|string|max:255'
        ]);

        $token = $request->user()->deviceTokens()->updateOrCreate(
            ['device_token' => $validated['device_token']],
            [
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'],
                'last_used_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'device_token' => $token
        ]);
    }

    /**
     * Remove a device token.
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $deleted = $request->user()->deviceTokens()
            ->where('device_token', $token)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Device unregistered successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Device not found'
        ], 404);
    }

    /**
     * List user's registered devices.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->deviceTokens()
            ->orderBy('last_used_at', 'desc')
            ->get(['id', 'platform', 'device_name', 'last_used_at', 'created_at']);

        return response()->json([
            'success' => true,
            'devices' => $tokens
        ]);
    }

    /**
     * Send a test push notification (for development).
     */
    public function testPush(Request $request): JsonResponse
    {
        $service = app(PushNotificationService::class);

        $service->sendNotification(
            $request->user()->id,
            'Test Notification',
            'This is a test push notification from Vitalytics',
            ['test' => true, 'deep_link' => 'vitalytics://dashboard']
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification queued'
        ]);
    }
}
