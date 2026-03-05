<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        public string $deviceToken,
        public string $platform,
        public ?string $title,
        public ?string $message,
        public array $data = [],
        public bool $silent = false
    ) {}

    public function handle(): void
    {
        try {
            if ($this->platform === 'ios') {
                $this->sendApns();
            } else {
                $this->sendFcm();
            }
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'device_token' => $this->maskToken($this->deviceToken),
                'platform' => $this->platform,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Send via Apple Push Notification Service (APNs).
     * APNs requires HTTP/2, so we use cURL directly with proper options.
     */
    private function sendApns(): void
    {
        $keyId = config('services.apns.key_id');
        $teamId = config('services.apns.team_id');
        $bundleId = config('services.apns.bundle_id');
        $privateKeyPath = config('services.apns.private_key_path');
        $production = config('services.apns.production', false);

        if (!$keyId || !$teamId || !$bundleId || !$privateKeyPath) {
            Log::warning('APNs not configured, skipping iOS push notification');
            return;
        }

        // Generate JWT token for APNs
        $jwt = $this->generateApnsJwt($keyId, $teamId, $privateKeyPath);

        // Build payload
        $payload = json_encode($this->buildApnsPayload());

        // Determine APNs URL
        $url = $production
            ? "https://api.push.apple.com/3/device/{$this->deviceToken}"
            : "https://api.sandbox.push.apple.com/3/device/{$this->deviceToken}";

        // APNs requires HTTP/2, use cURL directly
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_HTTPHEADER => [
                'Authorization: bearer ' . $jwt,
                'apns-topic: ' . $bundleId,
                'apns-push-type: ' . ($this->silent ? 'background' : 'alert'),
                'apns-priority: ' . (($this->data['priority'] ?? '') === 'critical' ? '10' : '5'),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        if ($httpCode === 200) {
            Log::info('APNs notification sent', [
                'device_token' => $this->maskToken($this->deviceToken),
                'title' => $this->title
            ]);
        } else {
            $this->handleApnsErrorResponse($httpCode, $response);
        }
    }

    /**
     * Send via Firebase Cloud Messaging (FCM) for Android.
     */
    private function sendFcm(): void
    {
        $serverKey = config('services.fcm.server_key');

        if (!$serverKey) {
            Log::warning('FCM not configured, skipping Android push notification');
            return;
        }

        $payload = [
            'to' => $this->deviceToken,
            'priority' => ($this->data['priority'] ?? '') === 'critical' ? 'high' : 'normal',
        ];

        if ($this->silent) {
            $payload['data'] = $this->data;
        } else {
            $payload['notification'] = [
                'title' => $this->title,
                'body' => $this->message,
                'sound' => 'default',
            ];
            $payload['data'] = $this->data;
        }

        $response = Http::withHeaders([
            'Authorization' => "key={$serverKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->successful()) {
            $result = $response->json();

            if (($result['failure'] ?? 0) > 0) {
                $this->handleFcmError($result);
            } else {
                Log::info('FCM notification sent', [
                    'device_token' => $this->maskToken($this->deviceToken),
                    'title' => $this->title
                ]);
            }
        } else {
            Log::error('FCM request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('FCM request failed: ' . $response->status());
        }
    }

    /**
     * Generate JWT for APNs authentication.
     */
    private function generateApnsJwt(string $keyId, string $teamId, string $privateKeyPath): string
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $keyId,
        ];

        $claims = [
            'iss' => $teamId,
            'iat' => time(),
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $claimsEncoded = $this->base64UrlEncode(json_encode($claims));

        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if (!$privateKey) {
            throw new \Exception('Failed to load APNs private key');
        }

        $signature = '';
        openssl_sign(
            $headerEncoded . '.' . $claimsEncoded,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        // Convert signature from DER to raw format for ES256
        $signature = $this->derToRaw($signature);

        return $headerEncoded . '.' . $claimsEncoded . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * Build APNs payload.
     * Custom data is at root level alongside 'aps' (not nested inside custom_data).
     */
    private function buildApnsPayload(): array
    {
        if ($this->silent) {
            return array_merge([
                'aps' => [
                    'content-available' => 1
                ]
            ], $this->data);
        }

        return array_merge([
            'aps' => [
                'alert' => [
                    'title' => $this->title,
                    'body' => $this->message
                ],
                'sound' => 'default',
                'badge' => 1,
            ]
        ], $this->data);
    }

    /**
     * Handle APNs error response (for cURL-based requests).
     */
    private function handleApnsErrorResponse(int $httpCode, string $response): void
    {
        $decoded = json_decode($response, true);
        $error = $decoded['reason'] ?? 'Unknown error';

        Log::error('APNs notification failed', [
            'device_token' => $this->maskToken($this->deviceToken),
            'status' => $httpCode,
            'error' => $error,
            'response' => $response
        ]);

        // Remove invalid tokens
        if (in_array($error, ['BadDeviceToken', 'Unregistered', 'ExpiredToken', 'DeviceTokenNotForTopic'])) {
            DeviceToken::where('device_token', $this->deviceToken)->delete();
            Log::info('Removed invalid device token', [
                'device_token' => $this->maskToken($this->deviceToken),
                'reason' => $error
            ]);
            return; // Don't retry for invalid tokens
        }

        throw new \Exception("APNs error ({$httpCode}): {$error}");
    }

    /**
     * Handle FCM error response.
     */
    private function handleFcmError(array $result): void
    {
        $error = $result['results'][0]['error'] ?? 'Unknown error';

        Log::error('FCM notification failed', [
            'device_token' => $this->maskToken($this->deviceToken),
            'error' => $error
        ]);

        // Remove invalid tokens
        if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
            DeviceToken::where('device_token', $this->deviceToken)->delete();
            Log::info('Removed invalid device token', [
                'device_token' => $this->maskToken($this->deviceToken),
                'reason' => $error
            ]);
            return;
        }

        throw new \Exception("FCM error: {$error}");
    }

    /**
     * Base64 URL encode.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Convert DER signature to raw format for ES256.
     */
    private function derToRaw(string $der): string
    {
        $sig = [];
        $pos = 0;

        // Skip sequence header
        $pos += 2;

        // Read R
        $rLen = ord($der[$pos + 1]);
        $r = substr($der, $pos + 2, $rLen);
        $pos += 2 + $rLen;

        // Read S
        $sLen = ord($der[$pos + 1]);
        $s = substr($der, $pos + 2, $sLen);

        // Pad/trim to 32 bytes each
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * Mask token for logging.
     */
    private function maskToken(string $token): string
    {
        return substr($token, 0, 10) . '...' . substr($token, -4);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'device_token' => $this->maskToken($this->deviceToken),
            'platform' => $this->platform,
            'title' => $this->title,
            'error' => $exception->getMessage()
        ]);
    }
}
