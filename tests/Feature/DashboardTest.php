<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Country;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProjectCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function developer(string $name, array $attributes = [], array $skills = []): User
    {
        $user = User::factory()->create(array_merge(['name' => $name, 'profile_completed_at' => now()], $attributes));

        foreach ($skills as $skill) {
            $user->skills()->attach($skill->id, ['level' => 'expert']);
        }

        return $user;
    }

    public function test_the_dashboard_welcomes_the_member_with_their_numbers(): void
    {
        $alpha = $this->developer('Alpha Keita', ['first_name' => 'Alpha', 'devscore' => 0]);
        app(ProjectCollaboration::class)->createProject($alpha, [
            'title' => 'AfricaDelivery', 'description' => 'Livraison.', 'category' => 'logistics', 'status' => 'recruiting', 'members_wanted' => 2,
        ]);
        Resource::factory()->create(['user_id' => $alpha->id]);

        $response = $this->actingAs($alpha)->get('/dashboard')->assertOk()
            ->assertSee('Bonjour Alpha')
            ->assertSee('Développeurs recommandés')
            ->assertSee('Projets qui recrutent')
            ->assertSee('Fil d\'activité');

        $this->assertSame(['projects' => 1, 'resources' => 1, 'collaborations' => 0, 'followers' => 0], $response->viewData('stats'));
    }

    public function test_recommendations_target_the_open_roles_of_my_projects(): void
    {
        $flutter = Skill::factory()->create(['name' => 'Flutter', 'slug' => 'flutter', 'category' => 'mobile']);
        $owner = $this->developer('Alpha');
        app(ProjectCollaboration::class)->createProject($owner, [
            'title' => 'AfricaDelivery', 'description' => 'Livraison.', 'category' => 'logistics', 'status' => 'recruiting', 'members_wanted' => 2,
            'roles' => [['title' => 'Flutter Developer', 'skill_id' => $flutter->id, 'slots' => 1]],
        ]);

        $this->developer('Ibrahim Flutter', [], [$flutter]);
        $this->developer('Private Flutter', ['is_public' => false], [$flutter]);
        $this->developer('Laravel Only', [], [Skill::factory()->create(['category' => 'backend'])]);

        $recommended = $this->actingAs($owner)->get('/dashboard')->viewData('recommendedDevelopers');

        $this->assertSame(['Ibrahim Flutter'], $recommended->pluck('user.name')->all());
        $this->assertNotNull($recommended->first()['score']);
    }

    public function test_without_open_roles_recommendations_come_from_my_country(): void
    {
        [$guinea, $senegal] = Country::factory(2)->create();
        $me = $this->developer('Me', ['country_id' => $guinea->id]);
        $followed = $this->developer('Already Followed', ['country_id' => $guinea->id]);
        $me->following()->attach($followed->id);
        $this->developer('Guinean Dev', ['country_id' => $guinea->id]);
        $this->developer('Senegalese Dev', ['country_id' => $senegal->id]);

        $recommended = $this->actingAs($me)->get('/dashboard')->viewData('recommendedDevelopers');

        $this->assertSame(['Guinean Dev'], $recommended->pluck('user.name')->all());
    }

    public function test_interesting_projects_match_my_skills_first(): void
    {
        $laravel = Skill::factory()->create();
        $me = $this->developer('Me', [], [$laravel]);

        $matching = Project::factory()->create(['title' => 'Needs Laravel', 'created_at' => now()->subWeek()]);
        $matching->roles()->create(['title' => 'Laravel Dev', 'skill_id' => $laravel->id, 'slots' => 1]);
        Project::factory()->create(['title' => 'Other Project']);
        Project::factory()->completed()->create(['title' => 'Finished']);

        $titles = $this->actingAs($me)->get('/dashboard')->viewData('interestingProjects')->pluck('title')->all();

        $this->assertSame(['Needs Laravel', 'Other Project'], $titles);
    }

    public function test_the_feed_shows_my_network_or_the_whole_community(): void
    {
        $me = $this->developer('Me');
        $friend = $this->developer('Friend');
        $stranger = $this->developer('Stranger');
        $private = $this->developer('Private', ['is_public' => false]);
        $me->following()->attach([$friend->id, $private->id]);

        foreach ([$friend, $stranger, $private] as $user) {
            Activity::log($user, ActivityType::ProjectCreated, Project::factory()->create(['owner_id' => $user->id]));
        }
        $deleted = Project::factory()->create(['owner_id' => $friend->id]);
        Activity::log($friend, ActivityType::ProjectCreated, $deleted);
        $deleted->delete();

        $network = $this->actingAs($me)->get('/dashboard')->viewData('feed');
        $this->assertSame(['Friend'], $network->pluck('user.name')->all());

        $all = $this->actingAs($me)->get('/dashboard?feed=all')->viewData('feed');
        $this->assertEqualsCanonicalizing(['Friend', 'Stranger'], $all->pluck('user.name')->all());
    }

    public function test_pending_invitations_and_applications_are_highlighted(): void
    {
        $owner = $this->developer('Owner');
        $collaboration = app(ProjectCollaboration::class);
        $project = $collaboration->createProject($owner, [
            'title' => 'AfricaDelivery', 'description' => 'Livraison.', 'category' => 'logistics', 'status' => 'recruiting', 'members_wanted' => 2,
        ]);
        $collaboration->apply($this->developer('Applicant'), $project, null, 'Bonjour');

        $this->actingAs($owner)->get('/dashboard')
            ->assertViewHas('pending', ['invitations' => 0, 'applications' => 1])
            ->assertSee('Une demande attend votre réponse');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_unverified_members_are_sent_to_the_verification_notice(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }
}
