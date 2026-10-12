<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password',
    'first_name', 'last_name', 'username', 'avatar_path',
    'country_id', 'city', 'bio',
    'profile_type', 'experience_level', 'availability',
    'github_url', 'linkedin_url', 'portfolio_url', 'website_url',
    'is_public', 'show_email', 'show_availability',
    'notify_by_email', 'notify_on_platform',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Same defaults as the database, so a user created in this request already has them
     * (otherwise `notify_on_platform` or `is_public` would read as null until reloaded).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'profile_type' => 'developer',
        'availability' => 'available',
        'devscore' => 0,
        'is_public' => true,
        'show_email' => false,
        'show_availability' => true,
        'notify_by_email' => true,
        'notify_on_platform' => true,
        'is_admin' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_type' => ProfileType::class,
            'experience_level' => ExperienceLevel::class,
            'availability' => Availability::class,
            'devscore' => 'integer',
            'profile_completed_at' => 'datetime',
            'is_public' => 'boolean',
            'show_email' => 'boolean',
            'show_availability' => 'boolean',
            'notify_by_email' => 'boolean',
            'notify_on_platform' => 'boolean',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->username ??= static::uniqueUsername($user->name);
        });
    }

    /**
     * Build a unique username from a display name ("Alpha Keita" → "alpha-keita", "alpha-keita-2"…).
     */
    public static function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'dev';
        $username = $base;
        $suffix = 2;

        while (static::where('username', $username)->exists()) {
            $username = $base.'-'.$suffix++;
        }

        return $username;
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * Public URL of the profile photo, or null (the UI then shows the initials).
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path
            ? Storage::disk('public')->url($this->avatar_path)
            : null);
    }

    /**
     * "Alpha Keita" → "AK", used when there is no photo.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn () => Str::upper(
            collect(explode(' ', $this->name))->filter()->take(2)->map(fn (string $part) => Str::substr($part, 0, 1))->join('')
        ));
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('suspended_at');
    }

    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * Developers shown in listings (search, rankings, country counts):
     * public, not suspended, with a verified email and a completed profile.
     */
    public function scopeListed(Builder $query): void
    {
        $query->public()->active()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('profile_completed_at');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)
            ->using(SkillUser::class)
            ->withPivot('level')
            ->withTimestamps();
    }

    /** Users who follow this user. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    /** Users this user follows. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /** Projects the user is a member of (including the ones they own). */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot('id', 'role', 'project_role_id', 'joined_at')
            ->withTimestamps();
    }

    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(CollaborationRequest::class);
    }

    public function likedResources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_likes')->withPivot('created_at');
    }

    public function resourceReviews(): HasMany
    {
        return $this->hasMany(ResourceReview::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withPivot('awarded_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Whether this user saved the developer, resource or project in "Mes favoris".
     */
    public function hasFavorited(Model $model): bool
    {
        return $this->favorites()
            ->where('favoritable_type', $model->getMorphClass())
            ->where('favoritable_id', $model->getKey())
            ->exists();
    }

    /** Favorites pointing at this user (other users who saved this profile). */
    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function aiSearches(): HasMany
    {
        return $this->hasMany(AiSearch::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /** Conversations where the user is either participant. */
    public function conversations(): Builder
    {
        return Conversation::query()->involving($this);
    }

    /**
     * Messages received in any conversation and not read yet (badge of the navigation bar).
     */
    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->unread()
            ->where('sender_id', '!=', $this->id)
            ->whereHas('conversation', fn (Builder $conversation) => $conversation->involving($this))
            ->count();
    }
}
