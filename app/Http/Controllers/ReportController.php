<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Signalement" (cahier, section 22): profile, resource, project or comment.
 */
class ReportController extends Controller
{
    /** Type names used in URLs (/reports/resource/12) → models. */
    public const TYPES = [
        'developer' => User::class,
        'resource' => Resource::class,
        'project' => Project::class,
        'comment' => Comment::class,
    ];

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $target = $this->find($type, $id);
        $reporter = $request->user();

        abort_if($this->authorId($target) === $reporter->id, 422, 'Vous ne pouvez pas signaler votre propre contenu.');

        $validated = $request->validate(
            [
                'reason' => ['required', Rule::enum(ReportReason::class)],
                'details' => ['nullable', 'string', 'max:1000'],
            ],
            [],
            ['reason' => 'motif', 'details' => 'précisions'],
        );

        $alreadyReported = $target->reports()
            ->where('reporter_id', $reporter->id)
            ->where('status', ReportStatus::Pending)
            ->exists();

        if (! $alreadyReported) {
            $target->reports()->create([
                'reporter_id' => $reporter->id,
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
            ])->forceFill(['status' => ReportStatus::Pending])->save();
        }

        return back()->with('status', $alreadyReported
            ? 'Vous avez déjà signalé ce contenu, il est en cours d\'examen.'
            : 'Merci, votre signalement a été envoyé à l\'équipe de modération.');
    }

    private function find(string $type, int $id): Model
    {
        $model = self::TYPES[$type]::findOrFail($id);

        $visible = match (true) {
            $model instanceof Comment => $model->resource !== null && Gate::allows('view', $model->resource),
            $model instanceof Project => true,
            default => Gate::allows('view', $model),
        };
        abort_unless($visible, 404);

        return $model;
    }

    private function authorId(Model $target): ?int
    {
        return match (true) {
            $target instanceof User => $target->id,
            $target instanceof Project => $target->owner_id,
            default => $target->user_id,
        };
    }
}
