<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertSubscriber extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'email',
        'name',
        'receive_critical',
        'receive_noncritical',
        'is_enabled',
    ];

    protected $casts = [
        'receive_critical' => 'boolean',
        'receive_noncritical' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the email address for this subscriber
     */
    public function getEmailAddressAttribute(): ?string
    {
        if ($this->user_id && $this->user) {
            return $this->user->email;
        }
        return $this->email;
    }

    /**
     * Get the display name for this subscriber
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->name ?? $this->email ?? 'Unknown';
    }

    /**
     * Scope: Only enabled subscribers
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Subscribers for critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('receive_critical', true);
    }

    /**
     * Scope: Subscribers for non-critical alerts
     */
    public function scopeNoncritical($query)
    {
        return $query->where('receive_noncritical', true);
    }
}
