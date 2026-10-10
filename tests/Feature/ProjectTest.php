<?php

namespace Tests\Feature;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Country;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProjectCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function projectData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'AfricaDelivery',
            'description' => 'Application de livraison pour les commerces africains.',
            'category' => 'logistics',
            'country_id' => Country::factory()->create()->id,
            'status' => 'recruiting',
            'members_wanted' => 3,
        ], $overrides);
    }

    public function test_a_verified_user_can_create_a_project_with_roles_and_skills(): void
    {
        $owner = User::factory()->create(['devscore' => 0]);
        [$laravel, $flutter] = Skill::factory(2)->create();

        $response = $this->actingAs($owner)->post('/projects', $this->projectData([
            'skills' => [$laravel->id, $flutter->id],
            'roles' => [
                ['title' => 'Laravel Developer', 'skill_id' => $laravel->id, 'slots' => 1],
                ['title' => 'Flutter Developer', 'skill_id' => $flutter->id, 'slots' => 2],
                ['title' => '', 'skill_id' => '', 'slots' => 1], // empty row from the form, ignored
            ],
        ]));

        $project = Project::firstOrFail();
        $response->assertRedirect(route('projects.show', $project));

        $this->assertSame(['Laravel Developer', 'Flutter Developer'], $project->roles->pluck('title')->all());
        $this->assertSame(2, $project->roles->last()->slots);
        $this->assertCount(2, $project->skills);
        $this->assertSame(ProjectMemberRole::Owner, $project->membershipOf($owner)->role);
        $this->assertSame(50, $owner->fresh()->devscore);
        $this->assertDatabaseHas('activities', ['user_id' => $owner->id, 'type' => 'project_created']);
    }

    public function test_project_creation_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/projects', $this->projectData([
            'title' => '',
            'category' => 'spaceship',
            'status' => 'completed', // a new project cannot start completed
            'members_wanted' => 0,
        ]))->assertSessionHasErrors(['title', 'category', 'status', 'members_wanted']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_guests_and_unverified_users_cannot_create_projects(): void
    {
        $this->get('/projects/create')->assertRedirect('/login');
        $this->actingAs(User::factory()->unverified()->create())->get('/projects/create')->assertRedirect('/verify-email');
    }

    public function test_the_project_pages_are_public(): void
    {
        $project = Project::factory()->create(['title' => 'HealthTech Guinea']);

        $this->get('/projects')->assertOk()->assertSee('HealthTech Guinea');
        $this->get("/projects/{$project->slug}")->assertOk()->assertSee('HealthTech Guinea');
    }

    public function test_only_the_owner_can_edit_or_delete_the_project(): void
    {
        $project = Project::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/projects/{$project->slug}/edit")->assertForbidden();
        $this->actingAs($stranger)->put("/projects/{$project->slug}", $this->projectData())->assertForbidden();
        $this->actingAs($stranger)->delete("/projects/{$project->slug}")->assertForbidden();

        $this->actingAs($project->owner)->put("/projects/{$project->slug}", $this->projectData(['title' => 'Nouveau titre']))
            ->assertRedirect();
        $this->assertSame('Nouveau titre', $project->fresh()->title);

        $this->actingAs($project->owner)->delete("/projects/{$project->slug}")->assertRedirect('/projects');
        $this->assertSoftDeleted($project);
        $this->get("/projects/{$project->slug}")->assertNotFound();
    }

    public function test_completing_a_project_awards_points_once(): void
    {
        $owner = User::factory()->create(['devscore' => 0]);
        $project = app(ProjectCollaboration::class)->createProject($owner, $this->projectData());

        $this->actingAs($owner)->put("/projects/{$project->slug}", $this->projectData(['status' => 'completed']));
        $this->assertSame(ProjectStatus::Completed, $project->fresh()->status);
        $this->assertNotNull($project->fresh()->completed_at);
        $this->assertSame(150, $owner->fresh()->devscore); // 50 created + 100 completed

        // Re-opening then completing again does not give the 100 points twice.
        $this->actingAs($owner)->put("/projects/{$project->slug}", $this->projectData(['status' => 'in_progress']));
        $this->actingAs($owner)->put("/projects/{$project->slug}", $this->projectData(['status' => 'completed']));
        $this->assertSame(150, $owner->fresh()->devscore);
    }

    public function test_a_cover_image_can_be_uploaded(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/projects', $this->projectData([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(Project::first()->cover_path);
    }

    public function test_managers_can_add_and_remove_roles(): void
    {
        $owner = User::factory()->create();
        $project = app(ProjectCollaboration::class)->createProject($owner, $this->projectData());

        $this->actingAs($owner)->post("/projects/{$project->slug}/roles", ['title' => 'UI/UX Designer', 'slots' => 1])
            ->assertSessionHasNoErrors();
        $role = $project->roles()->firstOrFail();

        $this->actingAs(User::factory()->create())->delete("/projects/{$project->slug}/roles/{$role->id}")->assertForbidden();

        $this->actingAs($owner)->delete("/projects/{$project->slug}/roles/{$role->id}");
        $this->assertDatabaseCount('project_roles', 0);
    }

    public function test_a_role_cannot_be_managed_through_another_project(): void
    {
        $owner = User::factory()->create();
        $mine = app(ProjectCollaboration::class)->createProject($owner, $this->projectData());
        $other = Project::factory()->create();
        $foreignRole = $other->roles()->create(['title' => 'Dev', 'slots' => 1]);

        $this->actingAs($owner)->delete("/projects/{$mine->slug}/roles/{$foreignRole->id}")->assertNotFound();
        $this->assertModelExists($foreignRole);
    }
}
