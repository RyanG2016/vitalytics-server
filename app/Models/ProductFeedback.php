<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFeedback extends Model
{
    protected $table = 'product_feedback';

    protected $fillable = [
        'app_identifier',
        'device_id',
        'session_id',
        'message',
        'category',
        'rating',
        'email',
        'user_id',
        'screen',
        'app_version',
        'platform',
        'os_version',
        'country',
        'city',
        'metadata',
        'is_test',
        'is_read',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_test' => 'boolean',
        'is_read' => 'boolean',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid feedback categories
     */
    public const CATEGORIES = [
        'general' => 'General',
        'bug' => 'Bug Report',
        'feature-request' => 'Feature Request',
        'praise' => 'Praise',
        'support' => 'Support Request',
    ];

    /**
     * Scope: Production feedback only
     */
    public function scopeProduction($query)
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope: Test feedback only
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
        return $query->where('category', $category);
    }

    /**
     * Scope: Unread feedback only
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope: Filter by rating
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope: Filter by minimum rating
     */
    public function scopeMinRating($query, int $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Get the app relationship
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_identifier', 'identifier');
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
