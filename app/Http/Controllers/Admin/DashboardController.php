<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\ResourceStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Administration home with the statistics of the cahier (section 21):
 * users, projects, resources, countries, collaborations.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'users_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
                'suspended' => User::whereNotNull('suspended_at')->count(),
                'projects' => Project::count(),
                'projects_recruiting' => Project::where('status', ProjectStatus::Recruiting)->count(),
                'projects_completed' => Project::where('status', ProjectStatus::Completed)->count(),
                'resources' => Resource::where('status', ResourceStatus::Approved)->count(),
                'resources_pending' => Resource::where('status', ResourceStatus::Pending)->count(),
                'countries' => User::whereNotNull('country_id')->distinct()->count('country_id'),
                'collaborations' => ProjectMember::where('role', '!=', ProjectMemberRole::Owner)->count(),
                'reports_pending' => Report::pending()->count(),
            ],
            'topCountries' => Country::withCount('users')->orderByDesc('users_count')->limit(10)->get()
                ->filter(fn (Country $country) => $country->users_count > 0)->values(),
            'signupsPerMonth' => $this->signupsPerMonth(),
        ]);
    }

    /**
     * New members over the last 6 months (oldest first), ready for a chart: ["Mai 2026" => 12, …].
     *
     * @return array<string, int>
     */
    private function signupsPerMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $counts = User::where('created_at', '>=', $start)->get(['created_at'])
            ->countBy(fn (User $user) => $user->created_at->format('Y-m'));

        $months = [];
        for ($month = $start->copy(); $month <= now(); $month->addMonth()) {
            $label = ucfirst(Carbon::parse($month)->locale('fr')->translatedFormat('F Y'));
            $months[$label] = $counts[$month->format('Y-m')] ?? 0;
        }

        return $months;
    }
}
