<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RegistrationToken extends Model
{
    protected $fillable = [
        'app_identifier',
        'token_prefix',
        'token_hash',
        'name',
        'created_by',
        'expires_at',
        'max_uses',
        'uses_count',
        'is_revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'is_revoked' => 'boolean',
    ];

    /**
     * Token prefix format
     */
    const TOKEN_PREFIX = 'vit_reg_';

    /**
     * The user who created this token
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Device API keys registered with this token
     */
    public function deviceApiKeys(): HasMany
    {
        return $this->hasMany(DeviceApiKey::class);
    }

    /**
     * Audit logs related to this token
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(DeviceAuditLog::class);
    }

    /**
     * Scope: Active (not revoked, not expired, not exhausted)
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    /**
     * Scope: Tokens for a specific app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Scope: Find by prefix for validation
     */
    public function scopeByPrefix($query, string $prefix)
    {
        return $query->where('token_prefix', $prefix);
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if token has reached max uses
     */
    public function isExhausted(): bool
    {
        if ($this->max_uses === null) {
            return false;
        }
        return $this->uses_count >= $this->max_uses;
    }

    /**
     * Check if token is revoked
     */
    public function isRevoked(): bool
    {
        return $this->is_revoked;
    }

    /**
     * Check if token is valid (can be used)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isExhausted() && !$this->isRevoked();
    }

    /**
     * Get the status of the token
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_revoked) {
            return 'revoked';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isExhausted()) {
            return 'exhausted';
        }
        return 'active';
    }

    /**
     * Get formatted uses display
     */
    public function getUsesDisplayAttribute(): string
    {
        if ($this->max_uses === null) {
            return "{$this->uses_count} (unlimited)";
        }
        return "{$this->uses_count} / {$this->max_uses}";
    }

    /**
     * Verify a provided token against the stored hash
     */
    public function verifyToken(string $providedToken): bool
    {
        return hash('sha256', $providedToken) === $this->token_hash;
    }

    /**
     * Increment the uses count
     */
    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Revoke this token
     */
    public function revoke(): void
    {
        $this->update(['is_revoked' => true]);
    }

    /**
     * Generate a new registration token
     * Returns the full token (to be shown once) and the model
     */
    public static function generate(
        string $appIdentifier,
        int $createdBy,
        int $expiresInHours = 72,
        ?int $maxUses = 1,
        ?string $name = null
    ): array {
        // Generate the full token
        $token = self::TOKEN_PREFIX . Str::random(32);

        // Create the record
        $model = self::create([
            'app_identifier' => $appIdentifier,
            'token_prefix' => substr($token, 0, 12),
            'token_hash' => hash('sha256', $token),
            'name' => $name,
            'created_by' => $createdBy,
            'expires_at' => now()->addHours($expiresInHours),
            'max_uses' => $maxUses,
        ]);

        return [
            'token' => $token,
            'model' => $model,
        ];
    }

    /**
     * Find and validate a token from the full token string
     */
    public static function findAndValidate(string $providedToken): ?self
    {
        // Validate format
        if (!str_starts_with($providedToken, self::TOKEN_PREFIX)) {
            return null;
        }

        if (strlen($providedToken) !== strlen(self::TOKEN_PREFIX) + 32) {
            return null;
        }

        // Find by prefix
        $prefix = substr($providedToken, 0, 12);
        $token = self::byPrefix($prefix)->first();

        if (!$token) {
            return null;
        }

        // Verify hash
        if (!$token->verifyToken($providedToken)) {
            return null;
        }

        return $token;
    }
}
