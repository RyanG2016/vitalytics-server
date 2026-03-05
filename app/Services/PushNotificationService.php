<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send a notification to a user's devices.
     */
    public function sendNotification(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): void {
        $user = User::with('deviceTokens')->find($userId);

        if (!$user) {
            Log::warning("User not found for push notification", ['user_id' => $userId]);
            return;
        }

        $tokens = $user->deviceTokens;

        if ($tokens->isEmpty()) {
            Log::debug("No device tokens found for user", ['user_id' => $userId]);
            return;
        }

        foreach ($tokens as $device) {
            dispatch(new SendPushNotificationJob(
                $device->device_token,
                $device->platform,
                $title,
                $message,
                $data
            ));
        }

        Log::info("Push notifications queued", [
            'user_id' => $userId,
            'device_count' => $tokens->count(),
            'title' => $title
        ]);
    }

    /**
     * Send notification to specific platform only.
     */
    public function sendToplatform(
        int $userId,
        string $platform,
        string $title,
        string $message,
        array $data = []
    ): void {
        $user = User::with('deviceTokens')->find($userId);

        if (!$user) {
            return;
        }

        foreach ($user->deviceTokens()->where('platform', $platform)->get() as $device) {
            dispatch(new SendPushNotificationJob(
                $device->device_token,
                $device->platform,
                $title,
                $message,
                $data
            ));
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToMultipleUsers(
        array $userIds,
        string $title,
        string $message,
        array $data = []
    ): void {
        foreach ($userIds as $userId) {
            $this->sendNotification($userId, $title, $message, $data);
        }
    }

    /**
     * Send critical alert (higher priority) - respects health_alerts preference.
     */
    public function sendCriticalAlert(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): void {
        $data['priority'] = 'critical';
        $this->sendHealthAlert($userId, $title, $message, $data);
    }

    /**
     * Send a health alert - only to devices with health_alerts enabled.
     */
    public function sendHealthAlert(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): void {
        $user = User::with('deviceTokens')->find($userId);

        if (!$user) {
            Log::warning("User not found for health alert", ['user_id' => $userId]);
            return;
        }

        $tokens = $user->deviceTokens->where('health_alerts', true);

        if ($tokens->isEmpty()) {
            Log::debug("No devices with health_alerts enabled for user", ['user_id' => $userId]);
            return;
        }

        foreach ($tokens as $device) {
            dispatch(new SendPushNotificationJob(
                $device->device_token,
                $device->platform,
                $title,
                $message,
                $data
            ));
        }

        Log::info("Health alert notifications queued", [
            'user_id' => $userId,
            'device_count' => $tokens->count(),
            'title' => $title
        ]);
    }

    /**
     * Send a feedback alert - only to devices with feedback_alerts enabled.
     */
    public function sendFeedbackAlert(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): void {
        $user = User::with('deviceTokens')->find($userId);

        if (!$user) {
            Log::warning("User not found for feedback alert", ['user_id' => $userId]);
            return;
        }

        $tokens = $user->deviceTokens->where('feedback_alerts', true);

        if ($tokens->isEmpty()) {
            Log::debug("No devices with feedback_alerts enabled for user", ['user_id' => $userId]);
            return;
        }

        foreach ($tokens as $device) {
            dispatch(new SendPushNotificationJob(
                $device->device_token,
                $device->platform,
                $title,
                $message,
                $data
            ));
        }

        Log::info("Feedback alert notifications queued", [
            'user_id' => $userId,
            'device_count' => $tokens->count(),
            'title' => $title
        ]);
    }

    /**
     * Send silent notification (for background updates).
     */
    public function sendSilentNotification(int $userId, array $data = []): void
    {
        $user = User::with('deviceTokens')->find($userId);

        if (!$user) {
            return;
        }

        foreach ($user->deviceTokens as $device) {
            dispatch(new SendPushNotificationJob(
                $device->device_token,
                $device->platform,
                null,
                null,
                $data,
                true // silent flag
            ));
        }
    }
}
