<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppMetric extends Model
{
    protected $fillable = [
        'metric_id',
        'app_identifier',
        'device_id',
        'name',
        'data',
        'aggregate_type',
        'tags',
        'user_id',
        'is_test',
        'metric_timestamp',
        'received_at',
    ];

    protected $casts = [
        'data' => 'array',
        'tags' => 'array',
        'is_test' => 'boolean',
        'metric_timestamp' => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Scope: Filter by app identifier
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Filter by metric name
     */
    public function scopeNamed($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope: Filter by test data
     */
    public function scopeTestData($query, bool $isTest = true)
    {
        return $query->where('is_test', $isTest);
    }

    /**
     * Get a specific value from the data JSON
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
