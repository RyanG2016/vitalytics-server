<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HealthEvent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'health_events';

    protected $fillable = [
        'event_id',
        'batch_id',
        'app_identifier',
        'environment',
        'device_id',
        'user_id',
        'level',
        'message',
        'metadata',
        'stack_trace',
        'device_model',
        'os_version',
        'app_version',
        'build_number',
        'platform',
        'city',
        'region',
        'country',
        'event_timestamp',
        'received_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'stack_trace' => 'array',
        'event_timestamp' => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Scope: Filter by level
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Filter crashes
     */
    public function scopeCrashes($query)
    {
        return $query->where('level', 'crash');
    }

    /**
     * Scope: Filter errors
     */
    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    /**
     * Scope: Filter by app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Filter by time range
     */
    public function scopeInRange($query, $start, $end)
    {
        return $query->whereBetween('event_timestamp', [$start, $end]);
    }

    /**
     * Scope: Recent events
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('event_timestamp', '>=', now()->subHours($hours));
    }

    /**
     * Get formatted stack trace
     */
    public function getFormattedStackTraceAttribute(): ?string
    {
        if (!$this->stack_trace) {
            return null;
        }

        return implode("\n", $this->stack_trace);
    }

    /**
     * Check if this is a critical event
     */
    public function isCritical(): bool
    {
        return in_array($this->level, ['crash', 'error']);
    }
}
