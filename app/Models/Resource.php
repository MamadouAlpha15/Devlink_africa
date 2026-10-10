<?php

namespace App\Models;

use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'title', 'slug', 'description', 'type',
    'image_path', 'file_path', 'github_url', 'demo_url', 'country_id',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'status' => ResourceStatus::class,
            'downloads_count' => 'integer',
            'likes_count' => 'integer',
            'rating_avg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Resource $resource) {
            $resource->slug ??= Str::slug($resource->title).'-'.Str::lower(Str::random(6));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', ResourceStatus::Approved);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** Technologies used by the resource. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ResourceReview::class);
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_likes')->withPivot('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Recompute the cached rating average and likes count.
     */
    public function refreshStats(): void
    {
        $average = $this->reviews()->avg('rating');

        $this->forceFill([
            'rating_avg' => $average === null ? null : round((float) $average, 1),
            'likes_count' => $this->likers()->count(),
        ])->saveQuietly();
    }
}
