<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAuditLog extends Model
{
    /**
     * Disable updated_at since audit logs are immutable
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'event_type',
        'app_identifier',
        'device_id',
        'registration_token_id',
        'device_api_key_id',
        'user_id',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Event types
     */
    const EVENT_TOKEN_CREATED = 'token_created';
    const EVENT_TOKEN_USED = 'token_used';
    const EVENT_TOKEN_REVOKED = 'token_revoked';
    const EVENT_TOKEN_EXPIRED = 'token_expired';
    const EVENT_TOKEN_EXHAUSTED = 'token_exhausted';
    const EVENT_TOKEN_VALIDATION_FAILED = 'token_validation_failed';

    const EVENT_DEVICE_REGISTERED = 'device_registered';
    const EVENT_DEVICE_REGISTRATION_FAILED = 'device_registration_failed';
    const EVENT_DEVICE_ALREADY_REGISTERED = 'device_already_registered';

    const EVENT_KEY_CREATED = 'key_created';
    const EVENT_KEY_REVOKED = 'key_revoked';
    const EVENT_KEY_REGENERATED = 'key_regenerated';
    const EVENT_KEY_VALIDATED = 'key_validated';
    const EVENT_KEY_VALIDATION_FAILED = 'key_validation_failed';

    const EVENT_RATE_LIMITED = 'rate_limited';

    /**
     * The registration token involved (if any)
     */
    public function registrationToken(): BelongsTo
    {
        return $this->belongsTo(RegistrationToken::class);
    }

    /**
     * The device API key involved (if any)
     */
    public function deviceApiKey(): BelongsTo
    {
        return $this->belongsTo(DeviceApiKey::class);
    }

    /**
     * The user who performed the action (if any)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by event type
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope: Filter by app identifier
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Filter by device ID
     */
    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope: Recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get human-readable event type
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            self::EVENT_TOKEN_CREATED => 'Token Created',
            self::EVENT_TOKEN_USED => 'Token Used',
            self::EVENT_TOKEN_REVOKED => 'Token Revoked',
            self::EVENT_TOKEN_EXPIRED => 'Token Expired',
            self::EVENT_TOKEN_EXHAUSTED => 'Token Exhausted',
            self::EVENT_TOKEN_VALIDATION_FAILED => 'Token Validation Failed',
            self::EVENT_DEVICE_REGISTERED => 'Device Registered',
            self::EVENT_DEVICE_REGISTRATION_FAILED => 'Registration Failed',
            self::EVENT_DEVICE_ALREADY_REGISTERED => 'Device Already Registered',
            self::EVENT_KEY_CREATED => 'API Key Created',
            self::EVENT_KEY_REVOKED => 'API Key Revoked',
            self::EVENT_KEY_REGENERATED => 'API Key Regenerated',
            self::EVENT_KEY_VALIDATED => 'API Key Validated',
            self::EVENT_KEY_VALIDATION_FAILED => 'API Key Validation Failed',
            self::EVENT_RATE_LIMITED => 'Rate Limited',
            default => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }

    /**
     * Get event severity for styling
     */
    public function getSeverityAttribute(): string
    {
        return match ($this->event_type) {
            self::EVENT_TOKEN_CREATED,
            self::EVENT_DEVICE_REGISTERED,
            self::EVENT_KEY_CREATED,
            self::EVENT_KEY_VALIDATED => 'success',

            self::EVENT_TOKEN_USED,
            self::EVENT_TOKEN_EXPIRED,
            self::EVENT_TOKEN_EXHAUSTED,
            self::EVENT_KEY_REGENERATED => 'info',

            self::EVENT_TOKEN_REVOKED,
            self::EVENT_KEY_REVOKED => 'warning',

            self::EVENT_TOKEN_VALIDATION_FAILED,
            self::EVENT_DEVICE_REGISTRATION_FAILED,
            self::EVENT_DEVICE_ALREADY_REGISTERED,
            self::EVENT_KEY_VALIDATION_FAILED,
            self::EVENT_RATE_LIMITED => 'error',

            default => 'info',
        };
    }

    /**
     * Log a token creation event
     */
    public static function logTokenCreated(
        RegistrationToken $token,
        int $userId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'event_type' => self::EVENT_TOKEN_CREATED,
            'app_identifier' => $token->app_identifier,
            'registration_token_id' => $token->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => [
                'name' => $token->name,
                'expires_at' => $token->expires_at->toIso8601String(),
                'max_uses' => $token->max_uses,
            ],
        ]);
    }

    /**
     * Log a device registration event
     */
    public static function logDeviceRegistered(
        DeviceApiKey $deviceKey,
        ?RegistrationToken $token = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'event_type' => self::EVENT_DEVICE_REGISTERED,
            'app_identifier' => $deviceKey->app_identifier,
            'device_id' => $deviceKey->device_id,
            'registration_token_id' => $token?->id,
            'device_api_key_id' => $deviceKey->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => [
                'device_name' => $deviceKey->device_name,
                'device_hostname' => $deviceKey->device_hostname,
                'device_os' => $deviceKey->device_os,
            ],
        ]);
    }

    /**
     * Log a registration failure
     */
    public static function logRegistrationFailed(
        string $appIdentifier,
        string $reason,
        ?string $deviceId = null,
        ?int $tokenId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $additionalDetails = []
    ): self {
        return self::create([
            'event_type' => self::EVENT_DEVICE_REGISTRATION_FAILED,
            'app_identifier' => $appIdentifier,
            'device_id' => $deviceId,
            'registration_token_id' => $tokenId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => array_merge(['reason' => $reason], $additionalDetails),
        ]);
    }

    /**
     * Log a key revocation
     */
    public static function logKeyRevoked(
        DeviceApiKey $deviceKey,
        int $userId,
        ?string $reason = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'event_type' => self::EVENT_KEY_REVOKED,
            'app_identifier' => $deviceKey->app_identifier,
            'device_id' => $deviceKey->device_id,
            'device_api_key_id' => $deviceKey->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'details' => [
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Log a token revocation
     */
    public static function logTokenRevoked(
        RegistrationToken $token,
        int $userId,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'event_type' => self::EVENT_TOKEN_REVOKED,
            'app_identifier' => $token->app_identifier,
            'registration_token_id' => $token->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'details' => [
                'uses_at_revocation' => $token->uses_count,
            ],
        ]);
    }

    /**
     * Log rate limiting event
     */
    public static function logRateLimited(
        string $ipAddress,
        ?string $appIdentifier = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'event_type' => self::EVENT_RATE_LIMITED,
            'app_identifier' => $appIdentifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
