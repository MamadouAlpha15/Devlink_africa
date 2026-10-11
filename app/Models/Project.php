<?php

namespace App\Models;

use App\Enums\ProjectCategory;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'owner_id', 'title', 'slug', 'description', 'category',
    'country_id', 'status', 'members_wanted', 'cover_path',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ProjectCategory::class,
            'status' => ProjectStatus::class,
            'members_wanted' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            $project->slug ??= Str::slug($project->title).'-'.Str::lower(Str::random(6));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeRecruiting(Builder $query): void
    {
        $query->where('status', ProjectStatus::Recruiting);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** Technologies used by the project. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /** Profiles the project is recruiting ("Laravel Developer ×1"). */
    public function roles(): HasMany
    {
        return $this->hasMany(ProjectRole::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot('id', 'role', 'project_role_id', 'joined_at')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(CollaborationRequest::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function membershipOf(User $user): ?ProjectMember
    {
        return $this->memberships()->where('user_id', $user->id)->first();
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }

    /**
     * Owner and co-owners handle requests, roles and members.
     */
    public function isManagedBy(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->whereIn('role', [ProjectMemberRole::Owner, ProjectMemberRole::CoOwner])
            ->exists();
    }

    /**
     * Owner and co-owners, who receive the team's notifications.
     *
     * @return Collection<int, User>
     */
    public function managers(): Collection
    {
        return $this->members()
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::CoOwner->value])
            ->get();
    }

    /**
     * New members can join while the project is an idea, recruiting or in progress.
     */
    public function acceptsCollaborators(): bool
    {
        return in_array($this->status, [ProjectStatus::Idea, ProjectStatus::Recruiting, ProjectStatus::InProgress], true);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
