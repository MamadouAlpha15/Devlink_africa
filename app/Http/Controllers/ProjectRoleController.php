<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRoleRequest;
use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * "Recruter une équipe": roles the project is looking for (Laravel Developer ×1…).
 */
class ProjectRoleController extends Controller
{
    public function store(ProjectRoleRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('manage', $project);

        $project->roles()->create($request->validated());

        return back()->with('status', 'Poste ajouté.');
    }

    public function update(ProjectRoleRequest $request, Project $project, ProjectRole $role): RedirectResponse
    {
        Gate::authorize('manage', $project);

        $role->update($request->validated());

        return back()->with('status', 'Poste mis à jour.');
    }

    public function destroy(Project $project, ProjectRole $role): RedirectResponse
    {
        Gate::authorize('manage', $project);

        $role->delete();

        return back()->with('status', 'Poste supprimé.');
    }
}
