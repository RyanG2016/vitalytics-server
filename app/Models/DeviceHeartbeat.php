<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DeviceHeartbeat extends Model
{
    protected $fillable = [
        'product_id',
        'app_identifier',
        'device_id',
        'device_name',
        'device_model',
        'os_version',
        'app_version',
        'last_heartbeat_at',
        'last_alert_at',
        'is_monitoring',
        'snoozed_until',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'last_alert_at' => 'datetime',
        'is_monitoring' => 'boolean',
        'snoozed_until' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_identifier', 'identifier');
    }

    /**
     * Scope: Only devices being monitored
     */
    public function scopeMonitored($query)
    {
        return $query->where('is_monitoring', true);
    }

    /**
     * Scope: Devices that have missed their heartbeat
     */
    public function scopeMissedHeartbeat($query, int $timeoutMinutes)
    {
        return $query->where('last_heartbeat_at', '<', Carbon::now()->subMinutes($timeoutMinutes));
    }

    /**
     * Scope: Devices that haven't been alerted recently
     */
    public function scopeNotRecentlyAlerted($query, int $cooldownMinutes = 30)
    {
        return $query->where(function ($q) use ($cooldownMinutes) {
            $q->whereNull('last_alert_at')
              ->orWhere('last_alert_at', '<', Carbon::now()->subMinutes($cooldownMinutes));
        });
    }

    /**
     * Scope: Devices that are not currently snoozed
     */
    public function scopeNotSnoozed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('snoozed_until')
              ->orWhere('snoozed_until', '<=', Carbon::now());
        });
    }

    /**
     * Scope: Devices that are currently snoozed
     */
    public function scopeSnoozed($query)
    {
        return $query->where('snoozed_until', '>', Carbon::now());
    }

    /**
     * Check if this device has missed its heartbeat
     */
    public function hasMissedHeartbeat(int $timeoutMinutes): bool
    {
        return $this->last_heartbeat_at->lt(Carbon::now()->subMinutes($timeoutMinutes));
    }

    /**
     * Check if we should alert for this device
     */
    public function shouldAlert(int $cooldownMinutes = 30): bool
    {
        if (!$this->last_alert_at) {
            return true;
        }

        return $this->last_alert_at->lt(Carbon::now()->subMinutes($cooldownMinutes));
    }

    /**
     * Mark that an alert was sent
     */
    public function markAlerted(): void
    {
        $this->update(['last_alert_at' => Carbon::now()]);
    }

    /**
     * Clear the alert (heartbeat received)
     */
    public function clearAlert(): void
    {
        $this->update(['last_alert_at' => null]);
    }

    /**
     * Check if device is currently snoozed
     */
    public function isSnoozed(): bool
    {
        return $this->snoozed_until && $this->snoozed_until->isFuture();
    }

    /**
     * Snooze alerts for this device for a specified number of hours
     */
    public function snoozeForHours(int $hours): void
    {
        $this->update(['snoozed_until' => Carbon::now()->addHours($hours)]);
    }

    /**
     * Cancel snooze and resume monitoring
     */
    public function cancelSnooze(): void
    {
        $this->update(['snoozed_until' => null]);
    }

    /**
     * Get remaining snooze time as human-readable string
     */
    public function getSnoozeRemainingAttribute(): ?string
    {
        if (!$this->isSnoozed()) {
            return null;
        }

        return $this->snoozed_until->diffForHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Update or create heartbeat record for a device
     */
    public static function recordHeartbeat(
        int $productId,
        string $appIdentifier,
        string $deviceId,
        array $deviceInfo = []
    ): self {
        $heartbeat = self::updateOrCreate(
            [
                'app_identifier' => $appIdentifier,
                'device_id' => $deviceId,
            ],
            [
                'product_id' => $productId,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'device_model' => $deviceInfo['device_model'] ?? null,
                'os_version' => $deviceInfo['os_version'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
                'last_heartbeat_at' => Carbon::now(),
            ]
        );

        // Clear any previous alert since we received a heartbeat
        if ($heartbeat->last_alert_at) {
            $heartbeat->clearAlert();
        }

        return $heartbeat;
    }

    /**
     * Get display name for the device
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->device_name) {
            return $this->device_name;
        }

        if ($this->device_model) {
            return $this->device_model;
        }

        return 'Device ' . substr($this->device_id, 0, 8);
    }

    /**
     * Get time since last heartbeat
     */
    public function getTimeSinceHeartbeatAttribute(): string
    {
        return $this->last_heartbeat_at->diffForHumans();
    }
}
