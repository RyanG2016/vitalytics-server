<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AnalyticsSession extends Model
{
    protected $fillable = [
        'session_id',
        'app_identifier',
        'device_id',
        'anonymous_user_id',
        'started_at',
        'ended_at',
        'last_activity_at',
        'duration_seconds',
        'event_count',
        'screens_viewed',
        'platform',
        'app_version',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'is_test',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_test' => 'boolean',
        'event_count' => 'integer',
        'screens_viewed' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Scope: Production sessions only
     */
    public function scopeProduction($query)
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope: Test sessions only
     */
    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope: Active sessions (activity in last 30 minutes)
     */
    public function scopeActive($query)
    {
        return $query->where('last_activity_at', '>=', Carbon::now()->subMinutes(30))
            ->whereNull('ended_at');
    }

    /**
     * Scope: Filter by app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Update or create session from event data
     */
    public static function recordActivity(
        string $sessionId,
        string $appIdentifier,
        string $deviceId,
        array $eventData,
        bool $isTest = false
    ): self {
        $session = self::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'app_identifier' => $appIdentifier,
                'device_id' => $deviceId,
                'anonymous_user_id' => $eventData['anonymous_user_id'] ?? null,
                'started_at' => Carbon::now(),
                'platform' => $eventData['platform'] ?? 'unknown',
                'app_version' => $eventData['app_version'] ?? null,
                'country' => $eventData['country'] ?? null,
                'region' => $eventData['region'] ?? null,
                'city' => $eventData['city'] ?? null,
                'latitude' => $eventData['latitude'] ?? null,
                'longitude' => $eventData['longitude'] ?? null,
                'is_test' => $isTest,
            ]
        );

        // Update activity
        $session->last_activity_at = Carbon::now();
        $session->event_count = $session->event_count + 1;

        // Track screen views
        if (isset($eventData['event_name']) && $eventData['event_name'] === 'screen_viewed') {
            $session->screens_viewed = $session->screens_viewed + 1;
        }

        // Calculate duration
        if ($session->started_at) {
            $session->duration_seconds = Carbon::now()->diffInSeconds($session->started_at);
        }

        $session->save();

        return $session;
    }

    /**
     * End the session
     */
    public function endSession(): void
    {
        $this->ended_at = Carbon::now();
        if ($this->started_at) {
            $this->duration_seconds = $this->ended_at->diffInSeconds($this->started_at);
        }
        $this->save();
    }

    /**
     * Get the app relationship
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_identifier', 'identifier');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '0s';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }
}
