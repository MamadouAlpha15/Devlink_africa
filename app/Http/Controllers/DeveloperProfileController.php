<?php

namespace App\Http\Controllers;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use App\Enums\SkillLevel;
use App\Http\Requests\UpdateDeveloperProfileRequest;
use App\Models\Country;
use App\Models\Skill;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * "Mon profil développeur": the profile completed after registration.
 * (Account settings — email, password, deletion — stay in Breeze's ProfileController.)
 */
class DeveloperProfileController extends Controller
{
    public function edit(Request $request, ProfileCompletion $completion): View
    {
        $user = $request->user()->load(['country', 'skills']);

        return view('developer-profile.edit', [
            'user' => $user,
            'completion' => $completion->percentage($user),
            'countries' => Country::orderBy('name')->get(),
            'skillsByCategory' => Skill::orderBy('name')->get()->groupBy(fn (Skill $skill) => $skill->category->label()),
            'profileTypes' => ProfileType::cases(),
            'experienceLevels' => ExperienceLevel::cases(),
            'availabilities' => Availability::cases(),
            'skillLevels' => SkillLevel::cases(),
        ]);
    }

    public function update(UpdateDeveloperProfileRequest $request, ProfileCompletion $completion): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        foreach (['is_public', 'show_email', 'show_availability', 'notify_by_email', 'notify_on_platform'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['name'] = trim($data['first_name'].' '.$data['last_name']);

        if ($request->hasFile('avatar') || $request->boolean('remove_avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->hasFile('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null;
        }

        $user->update($data);

        $justCompleted = $completion->sync($user);

        return redirect()->route('developer-profile.edit')->with('status', $justCompleted
            ? 'Profil complété ! Vous gagnez +'.config('devscore.points.profile_completed').' points DevScore.'
            : 'Profil mis à jour.');
    }
}
