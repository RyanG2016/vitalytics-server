<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

class MaintenanceNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'starts_at',
        'ends_at',
        'is_active',
        'severity',
        'dismissible',
        'is_test',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'dismissible' => 'boolean',
        'is_test' => 'boolean',
    ];

    /**
     * Products this notification applies to
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'maintenance_notification_product')
            ->withTimestamps();
    }

    /**
     * User who created the notification
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active notifications (is_active and within time window)
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    /**
     * Scope: Upcoming notifications (is_active and starts in future)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    /**
     * Scope: Expired notifications
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Scope: For a specific app identifier (finds via product relationship)
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        $app = App::where('identifier', $appIdentifier)->first();

        if (!$app || !$app->product_id) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->whereHas('products', function ($q) use ($app) {
            $q->where('products.id', $app->product_id);
        });
    }

    /**
     * Scope: For a specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->whereHas('products', function ($q) use ($productId) {
            $q->where('products.id', $productId);
        });
    }

    /**
     * Check if notification is currently active (within time window)
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->is_active
            && $this->starts_at <= $now
            && $this->ends_at >= $now;
    }

    /**
     * Check if notification is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at > now();
    }

    /**
     * Check if notification has expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at < now();
    }

    /**
     * Get active maintenance notifications for an app identifier
     *
     * @param string $appIdentifier The app identifier
     * @param bool $isTest Whether to include test notifications (true) or production notifications (false)
     */
    public static function getActiveForApp(string $appIdentifier, bool $isTest = false): Collection
    {
        return static::active()
            ->forApp($appIdentifier)
            ->where('is_test', $isTest)
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get formatted data for API response
     * Note: Returns timestamps in UTC for API consistency
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'dismissible' => $this->dismissible,
            'startsAt' => $this->starts_at->copy()->utc()->toIso8601String(),
            'endsAt' => $this->ends_at->copy()->utc()->toIso8601String(),
        ];
    }
}
