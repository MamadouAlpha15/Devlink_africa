<?php

namespace Tests\Feature;

use App\Enums\ProjectMemberRole;
use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Models\CollaborationRequest;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use App\Services\ProjectCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CollaborationRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Project $project;

    private ProjectRole $laravelRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->project = app(ProjectCollaboration::class)->createProject($this->owner, [
            'title' => 'AfricaDelivery',
            'description' => 'Livraison.',
            'category' => 'logistics',
            'status' => 'recruiting',
            'members_wanted' => 2,
            'roles' => [['title' => 'Laravel Developer', 'skill_id' => null, 'slots' => 1]],
        ]);
        $this->laravelRole = $this->project->roles()->firstOrFail();
    }

    private function apply(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->post("/projects/{$this->project->slug}/requests", $data + [
            'project_role_id' => $this->laravelRole->id,
            'message' => 'Bonjour, j\'aimerais rejoindre ton projet en tant que développeur Laravel.',
        ]);
    }

    public function test_a_developer_can_apply_and_the_owner_accepts(): void
    {
        $dev = User::factory()->create(['devscore' => 0]);

        $this->apply($dev)->assertSessionHasNoErrors();
        $request = CollaborationRequest::firstOrFail();
        $this->assertSame(RequestDirection::Application, $request->direction);
        $this->assertSame(RequestStatus::Pending, $request->status);

        // The applicant cannot accept their own application.
        $this->actingAs($dev)->post("/collaborations/{$request->id}/accept")->assertForbidden();

        $this->actingAs($this->owner)->post("/collaborations/{$request->id}/accept")->assertSessionHasNoErrors();

        $membership = $this->project->membershipOf($dev);
        $this->assertSame(ProjectMemberRole::Member, $membership->role);
        $this->assertSame($this->laravelRole->id, $membership->project_role_id);
        $this->assertSame(RequestStatus::Accepted, $request->fresh()->status);
        $this->assertSame(40, $dev->fresh()->devscore);
        $this->assertDatabaseHas('activities', ['user_id' => $dev->id, 'type' => 'project_joined']);
    }

    public function test_the_owner_can_reject_and_the_applicant_can_cancel(): void
    {
        [$a, $b] = User::factory(2)->create();

        $this->apply($a);
        $this->actingAs($this->owner)->post('/collaborations/'.CollaborationRequest::where('user_id', $a->id)->value('id').'/reject');
        $this->assertSame(RequestStatus::Rejected, CollaborationRequest::where('user_id', $a->id)->first()->status);
        $this->assertFalse($this->project->hasMember($a));

        $this->apply($b);
        $requestB = CollaborationRequest::where('user_id', $b->id)->first();
        $this->actingAs($this->owner)->delete("/collaborations/{$requestB->id}")->assertForbidden();
        $this->actingAs($b)->delete("/collaborations/{$requestB->id}");
        $this->assertSame(RequestStatus::Cancelled, $requestB->fresh()->status);
    }

    public function test_a_developer_cannot_apply_twice_or_when_already_member(): void
    {
        $dev = User::factory()->create();

        $this->apply($dev)->assertSessionHasNoErrors();
        $this->apply($dev)->assertSessionHasErrors('collaboration');
        $this->assertDatabaseCount('collaboration_requests', 1);

        $this->actingAs($this->owner)->post('/collaborations/'.CollaborationRequest::value('id').'/accept');
        $this->apply($dev)->assertForbidden();
        $this->apply($this->owner)->assertForbidden();
    }

    public function test_a_full_role_cannot_be_requested_or_accepted(): void
    {
        [$first, $second] = User::factory(2)->create();

        $this->apply($first);
        $this->apply($second);
        $this->actingAs($this->owner)->post('/collaborations/'.CollaborationRequest::where('user_id', $first->id)->value('id').'/accept');

        // The single Laravel slot is now taken.
        $this->actingAs($this->owner)
            ->post('/collaborations/'.CollaborationRequest::where('user_id', $second->id)->value('id').'/accept')
            ->assertSessionHasErrors('collaboration');
        $this->assertFalse($this->project->hasMember($second));

        $this->apply(User::factory()->create())->assertSessionHasErrors('project_role_id');
    }

    public function test_the_role_must_belong_to_the_project(): void
    {
        $foreignRole = Project::factory()->create()->roles()->create(['title' => 'Other', 'slots' => 1]);

        $this->apply(User::factory()->create(), ['project_role_id' => $foreignRole->id])
            ->assertSessionHasErrors('project_role_id');
    }

    public function test_completed_projects_do_not_accept_applications(): void
    {
        $this->project->update(['status' => 'completed']);

        $this->apply(User::factory()->create())->assertForbidden();
    }

    public function test_the_owner_can_invite_a_developer_who_accepts(): void
    {
        $dev = User::factory()->create(['devscore' => 0]);

        $this->actingAs($this->owner)->post("/projects/{$this->project->slug}/invitations", [
            'user_id' => $dev->id,
            'project_role_id' => $this->laravelRole->id,
            'message' => 'Ton profil correspond à notre projet !',
        ])->assertSessionHasNoErrors();

        $invitation = CollaborationRequest::firstOrFail();
        $this->assertSame(RequestDirection::Invitation, $invitation->direction);

        // The owner cannot answer their own invitation, only the invited developer can.
        $this->actingAs($this->owner)->post("/collaborations/{$invitation->id}/accept")->assertForbidden();

        $this->actingAs($dev)->post("/collaborations/{$invitation->id}/accept")->assertSessionHasNoErrors();
        $this->assertTrue($this->project->hasMember($dev));
        $this->assertSame(40, $dev->fresh()->devscore);
    }

    public function test_only_managers_can_invite_and_private_profiles_cannot_be_invited(): void
    {
        $dev = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/projects/{$this->project->slug}/invitations", ['user_id' => $dev->id])
            ->assertForbidden();

        $private = User::factory()->create(['is_public' => false]);
        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->slug}/invitations", ['user_id' => $private->id])
            ->assertNotFound();
    }

    public function test_my_collaborations_page_lists_what_concerns_me(): void
    {
        $dev = User::factory()->create(['name' => 'Ibrahim Sow']);
        $this->apply($dev);

        $this->actingAs($this->owner)->get('/collaborations')
            ->assertOk()
            ->assertSee('Ibrahim Sow')
            ->assertSee('AfricaDelivery');

        $this->actingAs($dev)->get('/collaborations')->assertOk()->assertSee('AfricaDelivery');
    }

    public function test_deleting_a_project_cancels_its_pending_requests(): void
    {
        $dev = User::factory()->create();
        $this->apply($dev);

        $this->actingAs($this->owner)->delete("/projects/{$this->project->slug}");

        $this->assertSame(RequestStatus::Cancelled, CollaborationRequest::first()->status);
        $this->actingAs($dev)->get('/collaborations')->assertOk();
    }
}
