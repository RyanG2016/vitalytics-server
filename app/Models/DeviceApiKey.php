<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeviceApiKey extends Model
{
    protected $fillable = [
        'app_identifier',
        'device_id',
        'device_name',
        'device_hostname',
        'device_os',
        'key_prefix',
        'key_hash',
        'registration_token_id',
        'registered_ip',
        'last_used_at',
        'last_used_ip',
        'is_revoked',
        'revoked_at',
        'revoked_by',
        'revoke_reason',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    /**
     * Key prefix for SpeechLive Helper devices
     */
    const KEY_PREFIX_SLH = 'slh_';

    /**
     * Default key prefix for other apps
     */
    const KEY_PREFIX_DEFAULT = 'dev_';

    /**
     * The registration token used to create this key
     */
    public function registrationToken(): BelongsTo
    {
        return $this->belongsTo(RegistrationToken::class);
    }

    /**
     * The user who revoked this key
     */
    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Audit logs related to this device key
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(DeviceAuditLog::class);
    }

    /**
     * Scope: Active (not revoked) keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }

    /**
     * Scope: Revoked keys
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    /**
     * Scope: Keys for a specific app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Find by key prefix for validation
     */
    public function scopeByPrefix($query, string $prefix)
    {
        return $query->where('key_prefix', $prefix);
    }

    /**
     * Scope: Find by device ID
     */
    public function scopeByDeviceId($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Check if key is revoked
     */
    public function isRevoked(): bool
    {
        return $this->is_revoked;
    }

    /**
     * Check if key is valid (can be used)
     */
    public function isValid(): bool
    {
        return !$this->isRevoked();
    }

    /**
     * Get the status of the key
     */
    public function getStatusAttribute(): string
    {
        return $this->is_revoked ? 'revoked' : 'active';
    }

    /**
     * Get display name (hostname or device name or truncated device_id)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->device_hostname) {
            return $this->device_hostname;
        }
        if ($this->device_name) {
            return $this->device_name;
        }
        return Str::limit($this->device_id, 12);
    }

    /**
     * Verify a provided API key against the stored hash
     */
    public function verifyKey(string $providedKey): bool
    {
        return hash('sha256', $providedKey) === $this->key_hash;
    }

    /**
     * Update last used timestamp
     */
    public function touchLastUsed(?string $ipAddress = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress,
        ]);
    }

    /**
     * Revoke this API key
     */
    public function revoke(?int $userId = null, ?string $reason = null): void
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_by' => $userId,
            'revoke_reason' => $reason,
        ]);
    }

    /**
     * Generate a new device API key
     * Returns the full key (to be shown once) and the model
     */
    public static function generate(
        string $appIdentifier,
        string $deviceId,
        ?int $registrationTokenId = null,
        ?string $ipAddress = null,
        ?string $deviceName = null,
        ?string $deviceHostname = null,
        ?string $deviceOs = null
    ): array {
        // Determine key prefix based on app
        $appPrefix = match ($appIdentifier) {
            'speechlive-helper' => self::KEY_PREFIX_SLH,
            default => self::KEY_PREFIX_DEFAULT,
        };

        // Build the full key: prefix + device_prefix + random
        $devicePrefix = substr(str_replace('-', '', $deviceId), 0, 8);
        $fullKey = $appPrefix . $devicePrefix . '_' . Str::random(32);

        // Create the record
        $model = self::create([
            'app_identifier' => $appIdentifier,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'device_hostname' => $deviceHostname,
            'device_os' => $deviceOs,
            'key_prefix' => substr($fullKey, 0, 12),
            'key_hash' => hash('sha256', $fullKey),
            'registration_token_id' => $registrationTokenId,
            'registered_ip' => $ipAddress,
        ]);

        return [
            'key' => $fullKey,
            'model' => $model,
        ];
    }

    /**
     * Find and validate an API key from the full key string
     */
    public static function findAndValidate(string $providedKey): ?self
    {
        // Validate minimum length
        if (strlen($providedKey) < 12) {
            return null;
        }

        // Find by prefix
        $prefix = substr($providedKey, 0, 12);
        $key = self::byPrefix($prefix)->first();

        if (!$key) {
            return null;
        }

        // Verify hash
        if (!$key->verifyKey($providedKey)) {
            return null;
        }

        return $key;
    }

    /**
     * Check if a device is already registered for an app
     */
    public static function isDeviceRegistered(string $appIdentifier, string $deviceId): bool
    {
        return self::forApp($appIdentifier)
            ->byDeviceId($deviceId)
            ->active()
            ->exists();
    }

    /**
     * Get the existing active key for a device (if any)
     */
    public static function getActiveKeyForDevice(string $appIdentifier, string $deviceId): ?self
    {
        return self::forApp($appIdentifier)
            ->byDeviceId($deviceId)
            ->active()
            ->first();
    }
}
