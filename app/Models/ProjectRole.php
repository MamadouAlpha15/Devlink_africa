<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'title', 'skill_id', 'slots'])]
class ProjectRole extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slots' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Number of free places ("Laravel Developer ×1" with nobody yet → 1).
     */
    public function openSlots(): int
    {
        $filled = $this->members_count ?? $this->members()->count();

        return max(0, $this->slots - $filled);
    }

    public function isFull(): bool
    {
        return $this->openSlots() === 0;
    }
}
