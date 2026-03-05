<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'has_health_access',
        'has_analytics_access',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'has_health_access' => 'boolean',
        'has_analytics_access' => 'boolean',
    ];

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a viewer.
     */
    public function isViewer(): bool
    {
        return $this->hasRole('viewer');
    }

    /**
     * Get the product slugs assigned to this user.
     */
    public function assignedProducts(): array
    {
        return \DB::table('user_products')
            ->where('user_id', $this->id)
            ->pluck('product_slug')
            ->toArray();
    }

    /**
     * Get the products this user can access.
     * Admins can access all products, viewers only assigned ones.
     */
    public function accessibleProducts(): array
    {
        if ($this->isAdmin()) {
            return Product::active()->pluck('slug')->toArray();
        }
        
        return $this->assignedProducts();
    }

    /**
     * Check if user can access a specific product.
     */
    public function canAccessProduct(string $productSlug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        return in_array($productSlug, $this->assignedProducts());
    }

    /**
     * Sync assigned products for the user.
     */
    public function syncProducts(array $productSlugs): void
    {
        \DB::table('user_products')->where('user_id', $this->id)->delete();

        foreach ($productSlugs as $slug) {
            \DB::table('user_products')->insert([
                'user_id' => $this->id,
                'product_slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Check if user can access Health dashboard.
     * Admins always have access, viewers need explicit permission.
     */
    public function canAccessHealth(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->has_health_access ?? true;
    }

    /**
     * Check if user can access Analytics dashboard.
     * Admins always have access, viewers need explicit permission.
     */
    public function canAccessAnalytics(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->has_analytics_access ?? true;
    }

    /**
     * Get dashboard access summary for display.
     */
    public function getDashboardAccessAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'All';
        }

        $access = [];
        if ($this->has_health_access) {
            $access[] = 'Health';
        }
        if ($this->has_analytics_access) {
            $access[] = 'Analytics';
        }

        return count($access) > 0 ? implode(' & ', $access) : 'None';
    }

    /**
     * Get the device tokens for push notifications.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
