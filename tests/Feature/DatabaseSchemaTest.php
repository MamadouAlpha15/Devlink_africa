<?php

namespace Tests\Feature;

use App\Enums\PointAction;
use App\Enums\ProjectMemberRole;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\DevScore;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_seeder_runs_and_keeps_devscores_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('countries', 54);
        $this->assertDatabaseHas('countries', ['code' => 'GN', 'name' => 'Guinée']);
        $this->assertDatabaseHas('skills', ['slug' => 'laravel']);
        $this->assertDatabaseHas('projects', ['title' => 'AfricaDelivery']);

        User::all()->each(fn (User $user) => $this->assertSame(
            (int) $user->pointTransactions()->sum('points'),
            $user->devscore,
            "DevScore out of sync for {$user->username}",
        ));
    }

    public function test_users_get_a_unique_username(): void
    {
        $first = User::factory()->create(['name' => 'Alpha Keita']);
        $second = User::factory()->create(['name' => 'Alpha Keita']);

        $this->assertSame('alpha-keita', $first->username);
        $this->assertSame('alpha-keita-2', $second->username);
    }

    public function test_devscore_award_records_the_transaction_and_updates_the_total(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        app(DevScore::class)->award($user, PointAction::ProjectCreated, $project);

        $this->assertSame(50, $user->fresh()->devscore);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'action' => 'project_created',
            'points' => 50,
            'subject_type' => 'project',
            'subject_id' => $project->id,
        ]);
    }

    public function test_project_members_expose_their_role(): void
    {
        $project = Project::factory()->create();
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $project->owner_id,
            'role' => ProjectMemberRole::Owner,
            'joined_at' => now(),
        ]);

        $member = $project->members()->first();

        $this->assertTrue($member->is($project->owner));
        $this->assertSame(ProjectMemberRole::Owner, $member->pivot->role);
    }

    public function test_a_pair_of_users_shares_a_single_conversation(): void
    {
        [$a, $b] = User::factory(2)->create();

        $this->assertTrue(Conversation::between($a, $b)->is(Conversation::between($b, $a)));
        $this->assertDatabaseCount('conversations', 1);
    }
}
