<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertHistory extends Model
{
    protected $table = 'alert_history';

    protected $fillable = [
        'product_id',
        'app_identifier',
        'level',
        'error_hash',
        'channel',
        'occurrence_count',
        'first_occurrence_at',
        'last_occurrence_at',
        'last_alerted_at',
        'cleared_at',
    ];

    protected $casts = [
        'first_occurrence_at' => 'datetime',
        'last_occurrence_at' => 'datetime',
        'last_alerted_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Generate a hash for an error message (for grouping similar errors)
     */
    public static function generateErrorHash(string $message): string
    {
        // Normalize the message by removing variable parts (numbers, IDs, etc.)
        $normalized = preg_replace('/\b\d+\b/', 'N', $message);
        $normalized = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', 'UUID', $normalized);
        $normalized = preg_replace('/0x[a-f0-9]+/i', 'HEX', $normalized);
        
        return md5($normalized);
    }

    /**
     * Find or create a history entry for this error
     */
    public static function findOrCreateForError(
        int $productId,
        ?string $appIdentifier,
        string $level,
        string $message,
        string $channel
    ): self {
        $hash = self::generateErrorHash($message);

        $history = self::where('product_id', $productId)
            ->where('app_identifier', $appIdentifier)
            ->where('level', $level)
            ->where('error_hash', $hash)
            ->where('channel', $channel)
            ->whereNull('cleared_at')
            ->first();

        if ($history) {
            $history->increment('occurrence_count');
            $history->update(['last_occurrence_at' => now()]);
            return $history;
        }

        return self::create([
            'product_id' => $productId,
            'app_identifier' => $appIdentifier,
            'level' => $level,
            'error_hash' => $hash,
            'channel' => $channel,
            'occurrence_count' => 1,
            'first_occurrence_at' => now(),
            'last_occurrence_at' => now(),
        ]);
    }

    /**
     * Check if this error should trigger an alert based on cooldown
     */
    public function shouldAlert(int $cooldownMinutes): bool
    {
        if (!$this->last_alerted_at) {
            return true;
        }

        return $this->last_alerted_at->addMinutes($cooldownMinutes)->isPast();
    }

    /**
     * Check if reminder should be sent (for critical errors not cleared)
     */
    public function shouldSendReminder(int $reminderHours): bool
    {
        if ($this->cleared_at) {
            return false;
        }

        if (!$this->last_alerted_at) {
            return false;
        }

        return $this->last_alerted_at->addHours($reminderHours)->isPast();
    }

    /**
     * Mark as alerted
     */
    public function markAlerted(): void
    {
        $this->update(['last_alerted_at' => now()]);
    }

    /**
     * Clear this error (no more reminders)
     */
    public function clear(): void
    {
        $this->update(['cleared_at' => now()]);
    }

    /**
     * Scope: Only active (not cleared) alerts
     */
    public function scopeActive($query)
    {
        return $query->whereNull('cleared_at');
    }

    /**
     * Scope: By level
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
}
