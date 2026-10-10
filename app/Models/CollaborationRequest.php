<?php

namespace App\Models;

use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'user_id', 'direction', 'project_role_id', 'message', 'status', 'responded_at'])]
class CollaborationRequest extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => RequestDirection::class,
            'status' => RequestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', RequestStatus::Pending);
    }

    /** Includes deleted projects so the request history still shows their title. */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /** The developer concerned (applicant or invitee). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectRole(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class);
    }
}
