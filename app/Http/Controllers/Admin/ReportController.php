<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reports: consult and handle (cahier, sections 21 and 22).
 */
class ReportController extends Controller
{
    /** What the admin can do with a report. */
    public const ACTIONS = [
        'dismiss' => 'Rejeter (rien à signaler)',
        'resolve' => 'Marquer comme traité',
        'remove' => 'Supprimer le contenu / suspendre le compte',
    ];

    public function index(Request $request): View
    {
        $status = ReportStatus::tryFrom((string) $request->query('status')) ?? ReportStatus::Pending;

        return view('admin.reports', [
            'reports' => Report::query()
                ->with([
                    'reporter',
                    'handler',
                    'reportable' => fn (MorphTo $morph) => $morph->morphWith([
                        Comment::class => ['resource', 'user'],
                        Resource::class => ['user'],
                        Project::class => ['owner'],
                    ]),
                ])
                ->where('status', $status)
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'statuses' => ReportStatus::cases(),
            'actions' => self::ACTIONS,
        ]);
    }

    public function handle(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate(['action' => ['required', Rule::in(array_keys(self::ACTIONS))]]);
        $target = $report->reportable;

        if ($validated['action'] === 'remove' && $target) {
            abort_if($target instanceof User && $target->is_admin, 403, 'Impossible de suspendre un administrateur.');
            $this->remove($target);
        }

        $handled = [
            'status' => $validated['action'] === 'dismiss' ? ReportStatus::Dismissed : ReportStatus::Resolved,
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ];

        // Removing the content also closes the other pending reports about it.
        $validated['action'] === 'remove'
            ? Report::pending()->where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)
                ->get()->each(fn (Report $sameTarget) => $sameTarget->forceFill($handled)->save())
            : $report->forceFill($handled)->save();

        return back()->with('status', 'Signalement traité.');
    }

    /**
     * A profile is suspended; a resource, project or comment is deleted.
     */
    private function remove(Model $target): void
    {
        match (true) {
            $target instanceof User => $target->forceFill(['suspended_at' => now()])->save(),
            $target instanceof Project => $this->deleteProject($target),
            default => $target->delete(),
        };
    }

    private function deleteProject(Project $project): void
    {
        $project->collaborationRequests()->pending()->update(['status' => RequestStatus::Cancelled, 'responded_at' => now()]);
        $project->delete();
    }

    /**
     * Link to the reported content for the admin, or null if it no longer exists.
     */
    public static function linkFor(Report $report): ?string
    {
        return match (true) {
            $report->reportable instanceof User => route('devs.show', $report->reportable),
            $report->reportable instanceof Resource => route('resources.show', $report->reportable),
            $report->reportable instanceof Project => route('projects.show', $report->reportable),
            $report->reportable instanceof Comment && $report->reportable->resource => route('resources.show', $report->reportable->resource),
            default => null,
        };
    }

    /**
     * "Profil : Alpha Keita", "Ressource : Wave API Client", "Commentaire de Ibrahim : « … »".
     */
    public static function describe(Report $report): string
    {
        return match (true) {
            $report->reportable instanceof User => 'Profil : '.$report->reportable->name,
            $report->reportable instanceof Resource => 'Ressource : '.$report->reportable->title,
            $report->reportable instanceof Project => 'Projet : '.$report->reportable->title,
            $report->reportable instanceof Comment => 'Commentaire de '.$report->reportable->user?->name.' : « '.str($report->reportable->body)->limit(80).' »',
            default => 'Contenu supprimé',
        };
    }
}
