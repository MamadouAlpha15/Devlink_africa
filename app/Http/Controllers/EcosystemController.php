<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Models\Country;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Bonus of the cahier (section 5): "L'écosystème DevLink Africa", statistics per country.
 * Only listed developers count (public, active, verified, completed profile), like the search.
 */
class EcosystemController extends Controller
{
    /** Countries drawn in the bar chart; every country stays in the table below it. */
    private const CHART_SIZE = 10;

    /** Skills drawn in the "most present skills" chart. */
    private const TOP_SKILLS = 8;

    /** Main skills shown for each country. */
    private const SKILLS_PER_COUNTRY = 3;

    public function __invoke(): View
    {
        $countries = $this->countries();

        return view('ecosystem.index', [
            'totals' => [
                'developers' => User::query()->listed()->count(),
                'countries' => $countries->where('developers_count', '>', 0)->count(),
                'projects' => Project::count(),
                'resources' => Resource::query()->published()->count(),
                'collaborations' => ProjectMember::where('role', '!=', ProjectMemberRole::Owner)->count(),
            ],
            'countries' => $countries,
            'chartCountries' => $countries->where('developers_count', '>', 0)->take(self::CHART_SIZE)->values(),
            'topSkills' => $this->topSkills(),
        ]);
    }

    /**
     * Every country with at least one developer, project or resource, biggest community first.
     *
     * @return Collection<int, Country>
     */
    private function countries(): Collection
    {
        $countries = Country::query()
            ->withCount([
                'users as developers_count' => fn (Builder $users) => $users->listed(),
                'projects',
                'resources' => fn (Builder $resources) => $resources->published(),
            ])
            ->get()
            ->filter(fn (Country $country) => $country->developers_count + $country->projects_count + $country->resources_count > 0)
            ->sortBy([['developers_count', 'desc'], ['projects_count', 'desc'], ['name', 'asc']])
            ->values();

        $topDevelopers = $this->topDeveloperPerCountry();
        $mainSkills = $this->mainSkillsPerCountry();

        return $countries->each(function (Country $country) use ($topDevelopers, $mainSkills): void {
            $country->setAttribute('top_developer', $topDevelopers->get($country->id));
            $country->setAttribute('main_skills', $mainSkills->get($country->id, collect()));
        });
    }

    /**
     * The listed developer with the highest DevScore in each country.
     *
     * @return Collection<int, User>
     */
    private function topDeveloperPerCountry(): Collection
    {
        $bestScores = User::query()
            ->listed()
            ->whereNotNull('country_id')
            ->groupBy('country_id')
            ->select('country_id', DB::raw('max(devscore) as best'));

        return User::query()
            ->listed()
            ->joinSub($bestScores, 'best_scores', fn ($join) => $join
                ->on('users.country_id', '=', 'best_scores.country_id')
                ->on('users.devscore', '=', 'best_scores.best'))
            ->orderBy('users.id')
            ->get(['users.*'])
            ->unique('country_id')
            ->keyBy('country_id');
    }

    /**
     * The skills declared by the most listed developers of each country.
     *
     * @return Collection<int, Collection<int, Skill>>
     */
    private function mainSkillsPerCountry(): Collection
    {
        return DB::table('skill_user')
            ->join('users', 'users.id', '=', 'skill_user.user_id')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->whereIn('users.id', User::query()->listed()->whereNotNull('country_id')->select('id'))
            ->groupBy('users.country_id', 'skills.id', 'skills.name', 'skills.slug')
            ->select('users.country_id', 'skills.id', 'skills.name', 'skills.slug', DB::raw('count(*) as developers'))
            ->orderByDesc('developers')
            ->orderBy('skills.name')
            ->get()
            ->groupBy('country_id')
            ->map(fn (Collection $rows) => $rows->take(self::SKILLS_PER_COUNTRY)->values());
    }

    /**
     * @return Collection<int, Skill>
     */
    private function topSkills(): Collection
    {
        return Skill::query()
            ->withCount(['users as developers_count' => fn (Builder $users) => $users->listed()])
            ->orderByDesc('developers_count')
            ->orderBy('name')
            ->limit(self::TOP_SKILLS)
            ->get()
            ->filter(fn (Skill $skill) => $skill->developers_count > 0)
            ->values();
    }
}
