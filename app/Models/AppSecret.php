<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppSecret extends Model
{
    protected $fillable = [
        'app_identifier',
        'secret',
        'label',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if this secret is currently active (not expired)
     */
    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Check if this secret is expiring soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isFuture() && $this->expires_at->diffInDays(now()) <= 7;
    }

    /**
     * Validate a plain text secret against this hashed secret
     */
    public function validateSecret(string $plainSecret): bool
    {
        return Hash::check($plainSecret, $this->secret);
    }

    /**
     * Generate a new secret for an app identifier
     */
    public static function generateForApp(string $appIdentifier, ?string $label = null): array
    {
        // Generate a random secret with prefix for easy identification
        $plainSecret = 'vtx_' . Str::random(32);

        $secret = self::create([
            'app_identifier' => $appIdentifier,
            'secret' => Hash::make($plainSecret),
            'label' => $label ?? 'Primary',
            'expires_at' => null,
        ]);

        // Return both the model and plain secret (only time it's available)
        return [
            'secret' => $secret,
            'plainSecret' => $plainSecret,
        ];
    }

    /**
     * Rotate secret: create new one and set expiry on current ones
     */
    public static function rotateForApp(string $appIdentifier, int $gracePeriodDays = 30): array
    {
        // Set expiry on all current active secrets for this app
        self::where('app_identifier', $appIdentifier)
            ->whereNull('expires_at')
            ->orWhere(function ($query) use ($appIdentifier) {
                $query->where('app_identifier', $appIdentifier)
                    ->where('expires_at', '>', now());
            })
            ->update([
                'expires_at' => now()->addDays($gracePeriodDays),
                'label' => 'Rotating out',
            ]);

        // Generate new secret
        return self::generateForApp($appIdentifier, 'Primary');
    }

    /**
     * Extend expiry for a secret
     */
    public function extendExpiry(int $days): void
    {
        if ($this->expires_at === null) {
            return; // Already never expires
        }

        $this->expires_at = $this->expires_at->addDays($days);
        $this->save();
    }

    /**
     * Revoke this secret immediately
     */
    public function revoke(): void
    {
        $this->expires_at = now();
        $this->save();
    }

    /**
     * Find active secrets for an app
     */
    public static function findActiveForApp(string $appIdentifier)
    {
        return self::where('app_identifier', $appIdentifier)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Validate a secret for an app identifier
     */
    public static function validateForApp(string $appIdentifier, string $plainSecret): bool
    {
        $secrets = self::findActiveForApp($appIdentifier);

        foreach ($secrets as $secret) {
            if ($secret->validateSecret($plainSecret)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the API key for an app identifier from database
     */
    public static function getApiKeyForApp(string $appIdentifier): ?string
    {
        $app = App::where('identifier', $appIdentifier)
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->active())
            ->first();

        return $app?->decryptedApiKey();
    }
}
