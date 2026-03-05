<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_id',
        'batch_id',
        'app_identifier',
        'session_id',
        'anonymous_user_id',
        'event_name',
        'event_category',
        'screen_name',
        'element_id',
        'properties',
        'duration_ms',
        'referrer',
        'device_id',
        'device_model',
        'platform',
        'os_version',
        'app_version',
        'screen_resolution',
        'language',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'is_test',
        'event_timestamp',
        'received_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_test' => 'boolean',
        'event_timestamp' => 'datetime',
        'received_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    /**
     * Scope: Production events only
     */
    public function scopeProduction($query)
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope: Test events only
     */
    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope: Filter by app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('event_timestamp', [$start, $end]);
    }

    /**
     * Get the app relationship
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_identifier', 'identifier');
    }
}
