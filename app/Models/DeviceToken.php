<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'device_name',
        'last_used_at',
        'health_alerts',
        'feedback_alerts',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'health_alerts' => 'boolean',
        'feedback_alerts' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
