<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductIcon extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'icon_path',
        'icon_url',
        'color',
    ];

    /**
     * Get the icon URL (prioritize uploaded path, then external URL)
     */
    public function getIconAttribute(): ?string
    {
        if ($this->icon_path) {
            return asset('storage/' . $this->icon_path);
        }
        return $this->icon_url;
    }

    /**
     * Check if product has a custom icon
     */
    public function hasCustomIcon(): bool
    {
        return !empty($this->icon_path) || !empty($this->icon_url);
    }

    /**
     * Get custom icons for all products as array
     */
    public static function getIconsMap(): array
    {
        return static::all()->keyBy('product_id')->map(function ($icon) {
            return [
                'icon' => $icon->icon,
                'icon_path' => $icon->icon_path,
                'icon_url' => $icon->icon_url,
                'color' => $icon->color,
                'has_custom' => $icon->hasCustomIcon(),
            ];
        })->toArray();
    }
}
