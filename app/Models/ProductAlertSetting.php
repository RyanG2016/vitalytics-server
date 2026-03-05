<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAlertSetting extends Model
{
    protected $fillable = [
        'product_id',
        'teams_webhook_url',
        'teams_enabled',
        'email_enabled',
        'push_enabled',
        'alert_on_test_data',
        'heartbeat_enabled',
        'heartbeat_timeout_minutes',
        'ai_analysis_enabled',
        'ai_analysis_hour',
        'critical_cooldown_minutes',
        'critical_reminder_hours',
        'noncritical_threshold',
        'noncritical_window_minutes',
        'noncritical_cooldown_hours',
        'business_hours_only',
        'business_hours_start',
        'business_hours_end',
        'exclude_weekends',
        'timezone',
    ];

    protected $casts = [
        'teams_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'alert_on_test_data' => 'boolean',
        'heartbeat_enabled' => 'boolean',
        'ai_analysis_enabled' => 'boolean',
        'business_hours_only' => 'boolean',
        'business_hours_start' => 'datetime:H:i',
        'business_hours_end' => 'datetime:H:i',
        'exclude_weekends' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(AlertSubscriber::class, 'product_id', 'product_id');
    }

    /**
     * Get or create settings for a product
     */
    public static function forProduct(int $productId): self
    {
        return self::firstOrCreate(
            ['product_id' => $productId],
            [
                'teams_enabled' => false,
                'email_enabled' => true,
            ]
        );
    }

    /**
     * Check if within business hours
     */
    public function isWithinBusinessHours(): bool
    {
        if (!$this->business_hours_only) {
            return true;
        }

        $now = now()->setTimezone($this->timezone);

        // Check weekend exclusion first
        if ($this->exclude_weekends && $now->isWeekend()) {
            return false;
        }

        if (!$this->business_hours_start || !$this->business_hours_end) {
            return true;
        }

        $start = $now->copy()->setTimeFromTimeString($this->business_hours_start->format('H:i'));
        $end = $now->copy()->setTimeFromTimeString($this->business_hours_end->format('H:i'));

        // Handle overnight ranges (e.g., 22:00 to 06:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }
}
