<?php

namespace Tests\Feature;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_profiles_resources_projects_and_comments_can_be_reported(): void
    {
        $me = User::factory()->create();
        $resource = Resource::factory()->create();
        $comment = Comment::create(['resource_id' => $resource->id, 'user_id' => User::factory()->create()->id, 'body' => 'Spam !']);

        $targets = [
            'developer' => User::factory()->create(),
            'resource' => $resource,
            'project' => Project::factory()->create(),
            'comment' => $comment,
        ];

        foreach ($targets as $type => $target) {
            $this->actingAs($me)->post("/reports/{$type}/{$target->id}", ['reason' => 'spam', 'details' => 'Lien suspect'])
                ->assertSessionHas('status');
        }

        $this->assertSame(4, Report::count());
        $report = Report::where('reportable_type', 'comment')->firstOrFail();
        $this->assertSame(ReportReason::Spam, $report->reason);
        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
        $this->assertTrue($report->reportable->is($comment));
    }

    public function test_the_same_member_cannot_report_the_same_content_twice(): void
    {
        $me = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($me)->post("/reports/project/{$project->id}", ['reason' => 'scam']);
        $this->actingAs($me)->post("/reports/project/{$project->id}", ['reason' => 'scam'])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'déjà signalé'));

        $this->assertSame(1, Report::count());
    }

    public function test_own_content_hidden_content_and_invalid_reasons_are_refused(): void
    {
        $me = User::factory()->create();
        $mine = Resource::factory()->create(['user_id' => $me->id]);
        $private = User::factory()->create(['is_public' => false]);

        $this->actingAs($me)->post("/reports/developer/{$me->id}", ['reason' => 'spam'])->assertStatus(422);
        $this->actingAs($me)->post("/reports/resource/{$mine->id}", ['reason' => 'spam'])->assertStatus(422);
        $this->actingAs($me)->post("/reports/developer/{$private->id}", ['reason' => 'spam'])->assertNotFound();
        $this->actingAs($me)->post('/reports/project/'.Project::factory()->create()->id, ['reason' => 'boring'])->assertSessionHasErrors('reason');

        $this->assertSame(0, Report::count());
    }

    public function test_the_report_button_appears_on_other_peoples_content_only(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me)->get("/devs/{$other->username}")->assertSee('Signaler')->assertSee('Contenu inapproprié');
        $this->actingAs($me)->get("/devs/{$me->username}")->assertDontSee('Envoyer le signalement');
    }

    public function test_guests_cannot_report(): void
    {
        $project = Project::factory()->create();

        $this->post("/reports/project/{$project->id}", ['reason' => 'spam'])->assertRedirect('/login');
    }
}
