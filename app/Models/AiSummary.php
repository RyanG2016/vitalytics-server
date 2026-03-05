<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AiSummary extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'content',
        'summary_data',
        'period_start',
        'period_end',
        'generated_at',
    ];

    protected $casts = [
        'summary_data' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the product this summary belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Health summaries only
     */
    public function scopeHealth($query)
    {
        return $query->where('type', 'health');
    }

    /**
     * Scope: Analytics summaries only
     */
    public function scopeAnalytics($query)
    {
        return $query->where('type', 'analytics');
    }

    /**
     * Scope: Recent summaries (last 90 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('generated_at', '>=', Carbon::now()->subDays(90));
    }

    /**
     * Scope: For a specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get formatted generation date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->generated_at->format('M d, Y');
    }

    /**
     * Get formatted generation time
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->generated_at->format('g:i A');
    }

    /**
     * Get rendered HTML content from markdown
     */
    public function getRenderedContentAttribute(): string
    {
        $text = $this->content;

        // Convert headers (## and #)
        $text = preg_replace('/^### (.+)$/m', '<h4 class="text-base font-semibold text-gray-800 mt-4 mb-2">$1</h4>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h3 class="text-lg font-semibold text-gray-900 mt-5 mb-2 pb-1 border-b border-gray-200">$1</h3>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h2 class="text-xl font-bold text-gray-900 mt-4 mb-3">$1</h2>', $text);

        // Convert bold (**text**)
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-gray-900">$1</strong>', $text);

        // Convert italic (*text*)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);

        // Convert bullet points (- item or * item)
        $text = preg_replace('/^[\-\*] (.+)$/m', '<li class="ml-4 text-gray-700">$1</li>', $text);

        // Wrap consecutive li tags in ul
        $text = preg_replace('/(<li[^>]*>.*?<\/li>\n?)+/s', '<ul class="list-disc space-y-1 my-2">$0</ul>', $text);

        // Convert numbered lists (1. item)
        $text = preg_replace('/^(\d+)\. (.+)$/m', '<li class="ml-4 text-gray-700"><span class="font-medium">$1.</span> $2</li>', $text);

        // Wrap consecutive numbered li in ol
        $text = preg_replace('/(<li[^>]*><span class="font-medium">\d+\.<\/span>.*?<\/li>\n?)+/s', '<ol class="list-decimal space-y-1 my-2">$0</ol>', $text);

        // Convert line breaks (but not inside block elements)
        $text = nl2br($text);

        // Clean up extra br tags around block elements
        $text = preg_replace('/<br\s*\/?>\s*(<[huo])/i', '$1', $text);
        $text = preg_replace('/(<\/[huo][^>]*>)\s*<br\s*\/?>/i', '$1', $text);
        $text = preg_replace('/<br\s*\/?>\s*(<li)/i', '$1', $text);
        $text = preg_replace('/(<\/li>)\s*<br\s*\/?>/i', '$1', $text);

        return $text;
    }

    /**
     * Clean up old summaries (older than 90 days)
     */
    public static function cleanup(): int
    {
        return static::where('generated_at', '<', Carbon::now()->subDays(90))->delete();
    }
}
