<?php

namespace Tests\Feature;

use App\Enums\PointAction;
use App\Models\Country;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use App\Services\BadgeAwarder;
use App\Services\DevScore;
use App\Services\ProjectCollaboration;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    /**
     * @return list<string>
     */
    private function badgesOf(User $user): array
    {
        return $user->badges()->orderBy('slug')->pluck('slug')->all();
    }

    private function createProject(User $owner, string $status = 'recruiting'): Project
    {
        return app(ProjectCollaboration::class)->createProject($owner, [
            'title' => 'AfricaDelivery',
            'description' => 'Livraison.',
            'category' => 'logistics',
            'status' => $status,
            'members_wanted' => 3,
        ]);
    }

    public function test_creating_a_first_project_awards_the_badge_and_logs_it_in_the_feed(): void
    {
        $owner = User::factory()->create();

        $this->createProject($owner);

        $this->assertSame(['premier-projet'], $this->badgesOf($owner));
        $this->assertDatabaseHas('activities', ['user_id' => $owner->id, 'type' => 'badge_earned', 'subject_type' => 'badge']);
    }

    public function test_joining_a_project_awards_collaborator(): void
    {
        $project = $this->createProject(User::factory()->create());
        $dev = User::factory()->create();
        $request = app(ProjectCollaboration::class)->apply($dev, $project, null, null);

        app(ProjectCollaboration::class)->accept($request);

        $this->assertSame(['collaborateur'], $this->badgesOf($dev));
    }

    public function test_completing_a_project_awards_builder(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProject($owner);

        app(ProjectCollaboration::class)->updateProject($project, ['status' => 'completed']);

        $this->assertContains('builder', $this->badgesOf($owner));
    }

    public function test_reaching_expert_level_in_laravel_awards_the_badge(): void
    {
        $user = User::factory()->create();
        $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($user)->post('/profile/skills', ['skill_id' => $laravel->id, 'level' => 'advanced']);
        $this->assertSame([], $this->badgesOf($user));

        $this->actingAs($user)->patch('/profile/skills/laravel', ['level' => 'expert'])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Expert Laravel'));
        $this->assertSame(['expert-laravel'], $this->badgesOf($user));
    }

    public function test_three_published_resources_award_active_contributor(): void
    {
        $user = User::factory()->create();
        Resource::factory(2)->create(['user_id' => $user->id]);
        Resource::factory()->pending()->create(['user_id' => $user->id]);

        app(BadgeAwarder::class)->evaluate($user);
        $this->assertSame([], $this->badgesOf($user), 'A pending resource does not count.');

        $resource = Resource::factory()->create(['user_id' => $user->id]);
        app(DevScore::class)->award($user, PointAction::ResourcePublished, $resource);

        $this->assertSame(['contributeur-actif'], $this->badgesOf($user));
    }

    public function test_a_team_spanning_three_countries_makes_everyone_panafrican(): void
    {
        [$guinea, $senegal, $ivoryCoast] = Country::factory(3)->create();
        $owner = User::factory()->create(['country_id' => $guinea->id]);
        $project = $this->createProject($owner);

        $senegalese = User::factory()->create(['country_id' => $senegal->id]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $senegalese->id, 'role' => 'member', 'joined_at' => now()]);
        $this->assertNotContains('panafricain', $this->badgesOf($owner));

        // The third country arrives through an accepted request: the whole team gets the badge.
        $ivorian = User::factory()->create(['country_id' => $ivoryCoast->id]);
        $collaboration = app(ProjectCollaboration::class);
        $collaboration->accept($collaboration->apply($ivorian, $project, null, null));

        $this->assertContains('panafricain', $this->badgesOf($owner));
        $this->assertContains('panafricain', $this->badgesOf($senegalese));
        $this->assertContains('panafricain', $this->badgesOf($ivorian));
    }

    public function test_badges_are_awarded_only_once(): void
    {
        $owner = User::factory()->create();
        $this->createProject($owner);
        $this->createProject($owner);

        $this->assertSame(1, $owner->badges()->count());
        $this->assertSame(1, $owner->activities()->where('type', 'badge_earned')->count());
    }
}
