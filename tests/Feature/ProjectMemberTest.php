<?php

namespace Tests\Feature;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProjectCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->project = app(ProjectCollaboration::class)->createProject($this->owner, [
            'title' => 'FinTech Africa',
            'description' => 'Portefeuille mobile money.',
            'category' => 'fintech',
            'status' => 'in_progress',
            'members_wanted' => 3,
        ]);
    }

    private function addMember(ProjectMemberRole $role = ProjectMemberRole::Member): User
    {
        $user = User::factory()->create();
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return $user;
    }

    public function test_the_owner_can_promote_and_demote_a_member(): void
    {
        $member = $this->addMember();

        $this->actingAs($this->owner)->patch("/projects/{$this->project->slug}/members/{$member->username}", ['role' => 'co_owner']);
        $this->assertSame(ProjectMemberRole::CoOwner, $this->project->membershipOf($member)->role);
        $this->assertTrue($this->project->isManagedBy($member));

        $this->actingAs($this->owner)->patch("/projects/{$this->project->slug}/members/{$member->username}", ['role' => 'member']);
        $this->assertSame(ProjectMemberRole::Member, $this->project->membershipOf($member)->role);
    }

    public function test_nobody_can_become_owner_or_change_the_owner(): void
    {
        $member = $this->addMember();

        $this->actingAs($this->owner)
            ->patch("/projects/{$this->project->slug}/members/{$member->username}", ['role' => 'owner'])
            ->assertSessionHasErrors('role');

        $this->actingAs($this->owner)
            ->patch("/projects/{$this->project->slug}/members/{$this->owner->username}", ['role' => 'member'])
            ->assertSessionHasErrors('member');

        $this->assertSame(ProjectMemberRole::Owner, $this->project->membershipOf($this->owner)->role);
    }

    public function test_co_owners_cannot_promote(): void
    {
        $coOwner = $this->addMember(ProjectMemberRole::CoOwner);
        $member = $this->addMember();

        $this->actingAs($coOwner)
            ->patch("/projects/{$this->project->slug}/members/{$member->username}", ['role' => 'co_owner'])
            ->assertForbidden();
    }

    public function test_managers_can_remove_members_but_not_the_owner(): void
    {
        $coOwner = $this->addMember(ProjectMemberRole::CoOwner);
        $member = $this->addMember();
        $otherCoOwner = $this->addMember(ProjectMemberRole::CoOwner);

        $this->actingAs($coOwner)->delete("/projects/{$this->project->slug}/members/{$member->username}");
        $this->assertFalse($this->project->hasMember($member));

        // A co-owner cannot remove another co-owner, nor the owner.
        $this->actingAs($coOwner)->delete("/projects/{$this->project->slug}/members/{$otherCoOwner->username}")->assertForbidden();
        $this->actingAs($coOwner)->delete("/projects/{$this->project->slug}/members/{$this->owner->username}")->assertSessionHasErrors('member');

        // A simple member cannot remove anyone.
        $simple = $this->addMember();
        $this->actingAs($simple)->delete("/projects/{$this->project->slug}/members/{$coOwner->username}")->assertForbidden();

        $this->assertTrue($this->project->hasMember($this->owner));
    }

    public function test_members_can_leave_but_the_owner_cannot(): void
    {
        $member = $this->addMember();

        $this->actingAs($member)->post("/projects/{$this->project->slug}/leave");
        $this->assertFalse($this->project->hasMember($member));

        $this->actingAs($this->owner)->post("/projects/{$this->project->slug}/leave")->assertSessionHasErrors('member');
        $this->assertTrue($this->project->hasMember($this->owner));
    }

    public function test_the_project_page_shows_suggested_developers_to_managers_only(): void
    {
        $skill = Skill::factory()->create(['name' => 'Flutter']);
        $this->project->roles()->create(['title' => 'Flutter Developer', 'skill_id' => $skill->id, 'slots' => 1]);

        $candidate = User::factory()->create(['name' => 'Ibrahim Sow', 'profile_completed_at' => now(), 'availability' => 'available']);
        $candidate->skills()->attach($skill->id, ['level' => 'expert']);

        $managerView = $this->actingAs($this->owner)->get("/projects/{$this->project->slug}")->assertOk();
        $this->assertSame(['Ibrahim Sow'], $managerView->viewData('suggestions')->first()['developers']->pluck('name')->all());

        $this->actingAs(User::factory()->create())->get("/projects/{$this->project->slug}")
            ->assertOk()
            ->assertDontSee('Développeurs suggérés');
    }
}
