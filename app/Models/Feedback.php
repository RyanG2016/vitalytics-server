<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'feedback_id',
        'user_id',
        'type',
        'message',
        'page_url',
        'status',
        'admin_notes',
    ];

    /**
     * Type options
     */
    public const TYPES = [
        'feedback' => ['label' => 'General Feedback', 'icon' => 'fa-comment', 'color' => 'blue'],
        'feature' => ['label' => 'Feature Request', 'icon' => 'fa-lightbulb', 'color' => 'yellow'],
        'enhancement' => ['label' => 'Enhancement', 'icon' => 'fa-magic', 'color' => 'purple'],
        'bug' => ['label' => 'Bug Report', 'icon' => 'fa-bug', 'color' => 'red'],
    ];

    /**
     * Status options
     */
    public const STATUSES = [
        'new' => ['label' => 'New', 'color' => 'blue'],
        'reviewed' => ['label' => 'Reviewed', 'color' => 'yellow'],
        'in_progress' => ['label' => 'In Progress', 'color' => 'purple'],
        'completed' => ['label' => 'Completed', 'color' => 'green'],
        'declined' => ['label' => 'Declined', 'color' => 'gray'],
    ];

    /**
     * Boot method to generate feedback ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feedback) {
            if (empty($feedback->feedback_id)) {
                $feedback->feedback_id = self::generateFeedbackId();
            }
        });
    }

    /**
     * Generate a unique feedback ID
     */
    public static function generateFeedbackId(): string
    {
        do {
            $id = 'FB-' . strtoupper(Str::random(8));
        } while (self::where('feedback_id', $id)->exists());

        return $id;
    }

    /**
     * Get the user who submitted the feedback
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucfirst($this->type);
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return self::TYPES[$this->type]['icon'] ?? 'fa-comment';
    }

    /**
     * Get type color
     */
    public function getTypeColorAttribute(): string
    {
        return self::TYPES[$this->type]['color'] ?? 'gray';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }

    /**
     * Scope: New feedback only
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
