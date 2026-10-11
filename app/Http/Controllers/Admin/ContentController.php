<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Enums\ResourceStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resources (validate, delete) and projects (see, delete) — cahier, section 21.
 */
class ContentController extends Controller
{
    public function resources(Request $request): View
    {
        $status = ResourceStatus::tryFrom((string) $request->query('status'));

        return view('admin.resources', [
            'resources' => Resource::query()
                ->with(['user', 'country'])
                ->withCount('reports')
                ->when($status, fn (Builder $query) => $query->where('status', $status))
                ->orderByRaw('status = ? desc', [ResourceStatus::Pending->value]) // pending first
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status?->value,
            'statuses' => ResourceStatus::cases(),
        ]);
    }

    public function approve(Resource $resource): RedirectResponse
    {
        $resource->forceFill(['status' => ResourceStatus::Approved])->save();

        return back()->with('status', '« '.$resource->title.' » est validée et publiée.');
    }

    public function reject(Resource $resource): RedirectResponse
    {
        $resource->forceFill(['status' => ResourceStatus::Rejected])->save();

        return back()->with('status', '« '.$resource->title.' » est refusée : elle n\'est plus visible.');
    }

    public function destroyResource(Resource $resource): RedirectResponse
    {
        $resource->delete();

        return back()->with('status', '« '.$resource->title.' » a été supprimée.');
    }

    public function projects(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        return view('admin.projects', [
            'projects' => Project::query()
                ->with(['owner', 'country'])
                ->withCount(['memberships', 'reports'])
                ->when($q !== '', fn (Builder $query) => $query->where('title', 'like', '%'.addcslashes($q, '%_\\').'%'))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'q' => $q,
        ]);
    }

    public function destroyProject(Project $project): RedirectResponse
    {
        $project->collaborationRequests()->pending()->update(['status' => RequestStatus::Cancelled, 'responded_at' => now()]);
        $project->delete();

        return back()->with('status', 'Le projet « '.$project->title.' » a été supprimé.');
    }
}
