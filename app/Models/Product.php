<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'color',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the apps (sub-products) for this product
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /**
     * Get only active apps
     */
    public function activeApps(): HasMany
    {
        return $this->apps()->where('is_active', true);
    }

    /**
     * Get the custom icon if set (from product_icons table)
     */
    public function customIcon(): HasOne
    {
        return $this->hasOne(ProductIcon::class, 'product_id', 'slug');
    }

    /**
     * Get the alert settings for this product
     */
    public function alertSettings(): HasOne
    {
        return $this->hasOne(ProductAlertSetting::class);
    }

    /**
     * Get the alert subscribers for this product
     */
    public function alertSubscribers(): HasMany
    {
        return $this->hasMany(AlertSubscriber::class);
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Generate a slug from the name
     */
    public static function generateSlug(string $name): string
    {
        return Str::slug($name);
    }

    /**
     * Get all products as config-compatible array (for backward compatibility)
     */
    public static function toConfigArray(): array
    {
        $products = [];
        
        foreach (static::with('activeApps')->active()->get() as $product) {
            $subProducts = [];
            foreach ($product->activeApps as $app) {
                $subProducts[$app->identifier] = [
                    'name' => $app->name,
                    'platform' => $app->platform,
                    'icon' => $app->icon,
                    'color' => $app->color,
                    'api_key' => $app->decryptedApiKey(),
                ];
            }
            
            $products[$product->slug] = [
                'name' => $product->name,
                'description' => $product->description,
                'icon' => $product->icon,
                'color' => $product->color,
                'sub_products' => $subProducts,
            ];
        }
        
        return $products;
    }

    /**
     * Get API keys map (app_identifier => api_key) for quick validation
     */
    public static function getApiKeysMap(): array
    {
        $keys = [];
        
        $apps = App::whereHas('product', fn($q) => $q->active())
            ->active()
            ->whereNotNull('api_key_encrypted')
            ->get();
        
        foreach ($apps as $app) {
            $decrypted = $app->decryptedApiKey();
            if ($decrypted) {
                $keys[$app->identifier] = $decrypted;
            }
        }
        
        return $keys;
    }
}
