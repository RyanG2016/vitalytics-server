<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EventLabelMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_identifier',
        'mapping_type',
        'raw_value',
        'friendly_label',
        'client_suggested_label',
    ];

    /**
     * Valid mapping types
     */
    public const TYPES = ['screen', 'element', 'feature', 'form', 'event_type'];

    /**
     * Cache key prefix for mappings
     */
    private const CACHE_KEY_PREFIX = 'event_label_mappings_';
    private const CACHE_TTL_MINUTES = 60;

    /**
     * Get all mappings for an app identifier as a lookup array
     * Returns: ['screen' => ['raw_value' => 'Friendly Label', ...], ...]
     */
    public static function getMappingsForApp(string $appIdentifier): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $appIdentifier;

        return Cache::remember($cacheKey, self::CACHE_TTL_MINUTES * 60, function () use ($appIdentifier) {
            $mappings = [];
            foreach (self::TYPES as $type) {
                $mappings[$type] = [];
            }

            $records = static::where('app_identifier', $appIdentifier)->get();

            foreach ($records as $record) {
                $mappings[$record->mapping_type][$record->raw_value] = $record->friendly_label;
            }

            return $mappings;
        });
    }

    /**
     * Clear cache for an app identifier
     */
    public static function clearCacheForApp(string $appIdentifier): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $appIdentifier);
    }

    /**
     * Apply a friendly label if a mapping exists
     */
    public static function applyLabel(string $appIdentifier, string $type, ?string $rawValue): ?string
    {
        if (empty($rawValue)) {
            return null;
        }

        $mappings = self::getMappingsForApp($appIdentifier);

        return $mappings[$type][$rawValue] ?? null;
    }

    /**
     * Get display label - returns friendly label if mapped, otherwise raw value
     */
    public static function getDisplayLabel(string $appIdentifier, string $type, ?string $rawValue): ?string
    {
        if (empty($rawValue)) {
            return null;
        }

        $friendlyLabel = self::applyLabel($appIdentifier, $type, $rawValue);
        return $friendlyLabel ?? $rawValue;
    }

    /**
     * Boot method to clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($mapping) {
            self::clearCacheForApp($mapping->app_identifier);
        });

        static::deleted(function ($mapping) {
            self::clearCacheForApp($mapping->app_identifier);
        });
    }
}
