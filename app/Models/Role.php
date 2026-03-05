<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function admin(): ?Role
    {
        return static::where('slug', 'admin')->first();
    }

    public static function viewer(): ?Role
    {
        return static::where('slug', 'viewer')->first();
    }
}
