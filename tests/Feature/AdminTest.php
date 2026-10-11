<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\ResourceStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function report(object $target, string $reason = 'spam'): Report
    {
        return $target->reports()->create(['reporter_id' => User::factory()->create()->id, 'reason' => $reason]);
    }

    public function test_only_admins_reach_the_administration(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();

        foreach (['/admin', '/admin/users', '/admin/resources', '/admin/projects', '/admin/reports'] as $page) {
            $this->actingAs($this->admin)->get($page)->assertOk();
        }
    }

    public function test_the_statistics_cover_users_projects_resources_countries_and_collaborations(): void
    {
        Project::factory(2)->create();
        Resource::factory()->create();
        Resource::factory()->pending()->create();

        $stats = $this->actingAs($this->admin)->get('/admin')->viewData('stats');

        $this->assertSame(1, $stats['resources']);
        $this->assertSame(1, $stats['resources_pending']);
        $this->assertSame(2, $stats['projects']);
        $this->assertArrayHasKey('countries', $stats);
        $this->assertArrayHasKey('collaborations', $stats);
    }

    public function test_users_can_be_searched_suspended_and_reactivated(): void
    {
        $ibrahim = User::factory()->create(['name' => 'Ibrahim Sow']);
        User::factory()->create(['name' => 'Aminata Camara']);

        $found = $this->actingAs($this->admin)->get('/admin/users?q=ibrahim')->viewData('users');
        $this->assertSame(['Ibrahim Sow'], $found->pluck('name')->all());

        $this->actingAs($this->admin)->post("/admin/users/{$ibrahim->username}/suspend");
        $this->assertTrue($ibrahim->fresh()->isSuspended());
        $this->assertSame(['Ibrahim Sow'], $this->actingAs($this->admin)->get('/admin/users?filter=suspended')->viewData('users')->pluck('name')->all());

        $this->actingAs($this->admin)->post("/admin/users/{$ibrahim->username}/unsuspend");
        $this->assertFalse($ibrahim->fresh()->isSuspended());
    }

    public function test_a_suspended_member_cannot_log_in_and_is_logged_out(): void
    {
        $member = User::factory()->create(['email' => 'suspendu@example.com']);
        $this->actingAs($this->admin)->post("/admin/users/{$member->username}/suspend");
        auth()->logout();

        $this->post('/login', ['email' => 'suspendu@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Votre compte a été suspendu par un administrateur.']);
        $this->assertGuest();

        // Already logged in when suspended: logged out at the next page.
        $this->actingAs($member->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admins_cannot_suspend_or_delete_admins(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)->post("/admin/users/{$otherAdmin->username}/suspend")->assertForbidden();
        $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->username}")->assertForbidden();
    }

    public function test_a_user_can_be_deleted(): void
    {
        $member = User::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/users/{$member->username}")->assertRedirect('/admin/users');

        $this->assertModelMissing($member);
    }

    public function test_resources_can_be_validated_refused_and_deleted(): void
    {
        $pending = Resource::factory()->pending()->create();

        $this->assertSame([$pending->id], $this->actingAs($this->admin)->get('/admin/resources?status=pending')->viewData('resources')->pluck('id')->all());

        $this->actingAs($this->admin)->post("/admin/resources/{$pending->slug}/approve");
        $this->assertSame(ResourceStatus::Approved, $pending->fresh()->status);
        $this->get("/resources/{$pending->slug}")->assertOk();

        $this->actingAs($this->admin)->post("/admin/resources/{$pending->slug}/reject");
        $this->assertSame(ResourceStatus::Rejected, $pending->fresh()->status);

        $this->actingAs($this->admin)->delete("/admin/resources/{$pending->slug}");
        $this->assertSoftDeleted($pending);
    }

    public function test_projects_can_be_listed_and_deleted(): void
    {
        $project = Project::factory()->create(['title' => 'Arnaque Express']);

        $this->actingAs($this->admin)->get('/admin/projects?q=arnaque')->assertSee('Arnaque Express');
        $this->actingAs($this->admin)->delete("/admin/projects/{$project->slug}");

        $this->assertSoftDeleted($project);
    }

    public function test_reports_can_be_dismissed_or_resolved(): void
    {
        $first = $this->report(Project::factory()->create());
        $second = $this->report(Project::factory()->create());

        $this->actingAs($this->admin)->get('/admin/reports')->assertOk()->assertSee('Spam');

        $this->actingAs($this->admin)->post("/admin/reports/{$first->id}", ['action' => 'dismiss']);
        $this->actingAs($this->admin)->post("/admin/reports/{$second->id}", ['action' => 'resolve']);

        $this->assertSame(ReportStatus::Dismissed, $first->fresh()->status);
        $this->assertSame(ReportStatus::Resolved, $second->fresh()->status);
        $this->assertSame($this->admin->id, $second->fresh()->handled_by);
        $this->assertNotNull($second->fresh()->handled_at);
    }

    public function test_removing_reported_content_deletes_it_or_suspends_the_profile(): void
    {
        $resource = Resource::factory()->create();
        $comment = Comment::create(['resource_id' => $resource->id, 'user_id' => User::factory()->create()->id, 'body' => 'Achetez ici !']);
        $profile = User::factory()->create();

        $commentReport = $this->report($comment);
        $otherReportOnSameComment = $this->report($comment, 'inappropriate');
        $profileReport = $this->report($profile, 'scam');

        $this->actingAs($this->admin)->post("/admin/reports/{$commentReport->id}", ['action' => 'remove']);
        $this->assertSoftDeleted($comment);
        $this->assertSame(ReportStatus::Resolved, $otherReportOnSameComment->fresh()->status, 'Other reports on the same content are closed too.');

        $this->actingAs($this->admin)->post("/admin/reports/{$profileReport->id}", ['action' => 'remove']);
        $this->assertTrue($profile->fresh()->isSuspended());

        // The list still works when the reported content no longer exists.
        $this->actingAs($this->admin)->get('/admin/reports?status=resolved')->assertOk()->assertSee('Contenu supprimé');
    }
}
