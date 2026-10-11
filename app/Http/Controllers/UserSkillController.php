<?php

namespace App\Http\Controllers;

use App\Enums\SkillLevel;
use App\Http\Requests\StoreUserSkillRequest;
use App\Models\Badge;
use App\Models\Skill;
use App\Services\BadgeAwarder;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Skills of the logged-in user (add, change level, remove).
 */
class UserSkillController extends Controller
{
    /**
     * Add a skill, or update its level if the user already has it.
     */
    public function store(StoreUserSkillRequest $request, ProfileCompletion $completion, BadgeAwarder $badges): RedirectResponse
    {
        $user = $request->user();

        $user->skills()->syncWithoutDetaching([
            $request->integer('skill_id') => ['level' => $request->validated('level')],
        ]);

        $completion->sync($user);
        $earned = $badges->evaluate($user); // "Expert Laravel"

        return back()->with('status', $this->message('Compétence ajoutée.', $earned));
    }

    public function update(Request $request, Skill $skill, BadgeAwarder $badges): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', Rule::enum(SkillLevel::class)],
        ]);

        $request->user()->skills()->updateExistingPivot($skill->id, ['level' => $validated['level']]);
        $earned = $badges->evaluate($request->user());

        return back()->with('status', $this->message('Niveau mis à jour.', $earned));
    }

    /**
     * @param  Collection<int, Badge>  $earned
     */
    private function message(string $message, Collection $earned): string
    {
        return $earned->isEmpty()
            ? $message
            : $message.' Nouveau badge : '.$earned->map(fn (Badge $badge) => $badge->icon.' '.$badge->name)->join(', ').' !';
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse
    {
        $request->user()->skills()->detach($skill->id);

        return back()->with('status', 'Compétence supprimée.');
    }
}
