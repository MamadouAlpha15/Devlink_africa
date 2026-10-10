<?php

namespace App\Http\Controllers;

use App\Enums\SkillLevel;
use App\Http\Requests\StoreUserSkillRequest;
use App\Models\Skill;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Skills of the logged-in user (add, change level, remove).
 */
class UserSkillController extends Controller
{
    /**
     * Add a skill, or update its level if the user already has it.
     */
    public function store(StoreUserSkillRequest $request, ProfileCompletion $completion): RedirectResponse
    {
        $user = $request->user();

        $user->skills()->syncWithoutDetaching([
            $request->integer('skill_id') => ['level' => $request->validated('level')],
        ]);

        $completion->sync($user);

        return back()->with('status', 'Compétence ajoutée.');
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', Rule::enum(SkillLevel::class)],
        ]);

        $request->user()->skills()->updateExistingPivot($skill->id, ['level' => $validated['level']]);

        return back()->with('status', 'Niveau mis à jour.');
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse
    {
        $request->user()->skills()->detach($skill->id);

        return back()->with('status', 'Compétence supprimée.');
    }
}
