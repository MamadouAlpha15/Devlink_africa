<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /**
     * Find a tag by name or create it ("Mobile Money" → slug "mobile-money").
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return static::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }
}
