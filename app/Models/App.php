<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class App extends Model
{
    protected $fillable = [
        'product_id',
        'linked_app_id',
        'identifier',
        'name',
        'platform',
        'icon',
        'color',
        'api_key_encrypted',
        'api_key_prefix',
        'api_key_generated_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key_generated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    /**
     * Platform options with icons
     */
    public const PLATFORMS = [
        'ios' => ['name' => 'iOS', 'icon' => 'fa-apple'],
        'android' => ['name' => 'Android', 'icon' => 'fa-android'],
        'chrome' => ['name' => 'Chrome Extension', 'icon' => 'fa-chrome'],
        'windows' => ['name' => 'Windows', 'icon' => 'fa-windows'],
        'macos' => ['name' => 'macOS', 'icon' => 'fa-apple'],
        'web' => ['name' => 'Web/Portal', 'icon' => 'fa-globe'],
    ];

    /**
     * Get the product this app belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the app this one is linked to (shares API key with)
     */
    public function linkedApp(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_app_id');
    }

    /**
     * Get apps that are linked to this one (use this app's API key)
     */
    public function linkedFrom(): HasMany
    {
        return $this->hasMany(self::class, 'linked_app_id');
    }

    /**
     * Scope: Only active apps
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate app identifier from product slug and platform/suffix
     *
     * @param string $productSlug The product slug (e.g., 'myapp')
     * @param string $platform The platform type (e.g., 'web', 'ios')
     * @param string|null $customSuffix Optional custom suffix (e.g., 'marketing', 'admin')
     */
    public static function generateIdentifier(string $productSlug, string $platform, ?string $customSuffix = null): string
    {
        if ($customSuffix) {
            return $productSlug . '-' . $customSuffix;
        }

        $suffix = $platform === 'web' ? 'portal' : $platform;
        return $productSlug . '-' . $suffix;
    }

    /**
     * Generate a new API key and store encrypted
     * Returns the plain key (shown once only)
     */
    public function generateApiKey(): string
    {
        $plainKey = 'vtx_' . Str::random(32);
        
        $this->api_key_encrypted = Crypt::encryptString($plainKey);
        $this->api_key_prefix = substr($plainKey, 0, 12) . '...';
        $this->api_key_generated_at = now();
        $this->save();
        
        return $plainKey;
    }

    /**
     * Get decrypted API key (for validation)
     */
    public function decryptedApiKey(): ?string
    {
        if (!$this->api_key_encrypted) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if provided key matches this app's key or linked app's key
     */
    public function validateApiKey(string $providedKey): bool
    {
        // Check this app's own key first
        $storedKey = $this->decryptedApiKey();
        if ($storedKey && hash_equals($storedKey, $providedKey)) {
            return true;
        }

        // If linked to another app, check that app's key
        if ($this->linked_app_id && $this->linkedApp) {
            $linkedKey = $this->linkedApp->decryptedApiKey();
            if ($linkedKey && hash_equals($linkedKey, $providedKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find an app by identifier and validate API key
     */
    public static function findByApiKey(string $identifier, string $apiKey): ?self
    {
        $app = static::with('linkedApp')
            ->where('identifier', $identifier)
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->active())
            ->first();

        if ($app && $app->validateApiKey($apiKey)) {
            return $app;
        }

        return null;
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['name'] ?? ucfirst($this->platform);
    }

    /**
     * Get platform icon
     */

    /**
     * Get platform icon
     */
    public function getPlatformIconAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['icon'] ?? 'fa-cube';
    }

    /**
     * Get secrets for this app
     */
    public function secrets(): HasMany
    {
        return $this->hasMany(AppSecret::class, 'app_identifier', 'identifier');
    }

    /**
     * Get active secrets for this app
     */
    public function activeSecrets()
    {
        return $this->secrets()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
