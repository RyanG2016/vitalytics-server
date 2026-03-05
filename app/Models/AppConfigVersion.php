<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppConfigVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'app_config_id',
        'version',
        'content',
        'content_hash',
        'content_size',
        'change_notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'content_size' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            // Auto-compute hash and size
            $version->content_hash = hash('sha256', $version->content);
            $version->content_size = strlen($version->content);
            $version->created_at = now();
        });
    }

    /**
     * The config this version belongs to
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(AppConfig::class, 'app_config_id');
    }

    /**
     * The user who created this version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->content_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / 1048576, 1) . ' MB';
        }
    }
}
