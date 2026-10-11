<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\BadgeEarned;
use App\Notifications\CollaborationAccepted;
use App\Notifications\CollaborationRequested;
use App\Notifications\NewComment;
use App\Notifications\NewFollower;
use App\Notifications\ProjectJoined;
use App\Notifications\ResourcePublished;
use App\Services\ProjectCollaboration;
use App\Services\ResourceLibrary;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $owner): Project
    {
        return app(ProjectCollaboration::class)->createProject($owner, [
            'title' => 'AfricaDelivery', 'description' => 'Livraison.', 'category' => 'logistics',
            'status' => 'recruiting', 'members_wanted' => 2,
        ]);
    }

    public function test_an_application_notifies_the_team_and_its_acceptance_notifies_the_developer(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $project = $this->project($owner);
        $dev = User::factory()->create();

        $request = app(ProjectCollaboration::class)->apply($dev, $project, null, 'Bonjour');
        Notification::assertSentTo($owner, CollaborationRequested::class, fn ($notification, array $channels) => in_array('mail', $channels, true)
            && str_contains($notification->toArray($owner)['message'], 'souhaite rejoindre AfricaDelivery'));

        app(ProjectCollaboration::class)->accept($request);
        Notification::assertSentTo($dev, CollaborationAccepted::class);
    }

    public function test_an_invitation_notifies_the_developer_and_its_acceptance_notifies_the_team(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $project = $this->project($owner);
        $dev = User::factory()->create();

        $invitation = app(ProjectCollaboration::class)->invite($project, $dev, null, 'Rejoins-nous !');
        Notification::assertSentTo($dev, CollaborationRequested::class, fn ($notification) => str_contains($notification->toArray($dev)['message'], 'vous invite'));

        app(ProjectCollaboration::class)->accept($invitation);
        Notification::assertSentTo($owner, ProjectJoined::class);
    }

    public function test_follows_resources_and_comments_notify_the_right_people(): void
    {
        Notification::fake();
        [$author, $follower, $commenter] = User::factory(3)->create();

        $this->actingAs($follower)->post("/devs/{$author->username}/follow");
        $this->actingAs($follower)->post("/devs/{$author->username}/follow"); // twice: one notification
        Notification::assertSentToTimes($author, NewFollower::class, 1);

        $resource = app(ResourceLibrary::class)->publish($author, ['title' => 'Wave API', 'description' => 'Client.', 'type' => 'api']);
        Notification::assertSentTo($follower, ResourcePublished::class);
        Notification::assertNotSentTo($commenter, ResourcePublished::class);

        app(ResourceLibrary::class)->comment($commenter, $resource, 'Merci !');
        app(ResourceLibrary::class)->comment($author, $resource, 'Avec plaisir'); // own resource: no notification
        Notification::assertSentToTimes($author, NewComment::class, 1);
    }

    public function test_a_new_badge_is_notified(): void
    {
        Notification::fake();
        $this->seed(BadgeSeeder::class);
        $owner = User::factory()->create();

        $this->project($owner);

        Notification::assertSentTo($owner, BadgeEarned::class);
    }

    public function test_members_choose_platform_and_email_channels(): void
    {
        $owner = User::factory()->create();
        $project = $this->project($owner);
        $request = $project->collaborationRequests()->create(['user_id' => User::factory()->create()->id, 'direction' => 'application', 'status' => 'pending']);
        $notification = new CollaborationRequested($request);

        $this->assertSame(['database', 'mail'], $notification->via($owner));

        $owner->forceFill(['notify_by_email' => false])->save();
        $this->assertSame(['database'], $notification->via($owner));

        $owner->forceFill(['notify_on_platform' => false, 'notify_by_email' => true])->save();
        $this->assertSame(['mail'], $notification->via($owner));

        // Low-priority notifications never send emails.
        $this->assertSame([], (new NewFollower(User::factory()->create()))->via($owner));
    }

    public function test_the_bell_polls_new_notifications_since_the_last_check(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $me = User::factory()->create();
        $me->notify(new NewFollower(User::factory()->create(['name' => 'Ibrahim Sow'])));

        $first = $this->actingAs($me)->getJson('/notifications/poll')->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('notifications.0.message', 'Ibrahim Sow vous suit maintenant.')
            ->assertJsonPath('notifications.0.icon', '👋');
        $firstId = $first->json('notifications.0.id');

        // Same flow as the browser: each check sends the server_time of the previous answer.
        // Notifications from that same second may come back again; the browser drops ids it already has.
        Carbon::setTestNow('2026-10-12 10:00:10');
        $second = $this->actingAs($me)->getJson('/notifications/poll?since='.urlencode($first->json('server_time')));
        $this->assertSame([$firstId], array_column($second->json('notifications'), 'id'), 'Nothing new, only the already known one.');

        // A new notification arrives: the next check returns only it.
        Carbon::setTestNow('2026-10-12 10:00:15');
        $me->notify(new NewFollower(User::factory()->create(['name' => 'Aminata Camara'])));

        Carbon::setTestNow('2026-10-12 10:00:20');
        $this->actingAs($me)->getJson('/notifications/poll?since='.urlencode($second->json('server_time')))
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.message', 'Aminata Camara vous suit maintenant.')
            ->assertJsonPath('unread', 2);

        $this->actingAs($me)->getJson('/notifications/poll?since=not-a-date')->assertOk()->assertJsonCount(2, 'notifications');
    }

    public function test_opening_a_notification_marks_it_read_and_redirects(): void
    {
        $me = User::factory()->create();
        $follower = User::factory()->create();
        $me->notify(new NewFollower($follower));
        $notification = $me->notifications()->first();

        $this->actingAs($me)->get("/notifications/{$notification->id}")->assertRedirect(route('devs.show', $follower));
        $this->assertNotNull($notification->fresh()->read_at);

        // Someone else's notification is not reachable.
        $this->actingAs(User::factory()->create())->get("/notifications/{$notification->id}")->assertNotFound();
    }

    public function test_all_notifications_can_be_marked_read_and_listed(): void
    {
        $me = User::factory()->create();
        $me->notify(new NewFollower(User::factory()->create(['name' => 'Ibrahim Sow'])));
        $me->notify(new NewFollower(User::factory()->create()));

        $this->actingAs($me)->get('/notifications')->assertOk()->assertSee('Ibrahim Sow vous suit maintenant.');
        $this->actingAs($me)->postJson('/notifications/read-all')->assertJsonPath('unread', 0);
        $this->assertSame(0, $me->unreadNotifications()->count());
    }

    public function test_the_bell_is_in_the_navigation_and_guests_have_no_access(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertSee('notificationBell', false);

        auth()->logout();
        $this->getJson('/notifications/poll')->assertUnauthorized();
        $this->get('/notifications')->assertRedirect('/login');
    }
}
