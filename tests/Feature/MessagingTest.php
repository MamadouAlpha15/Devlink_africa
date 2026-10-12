<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function send(User $sender, Conversation $conversation, string $body = 'Bonjour !'): TestResponse
    {
        return $this->actingAs($sender)->postJson("/messages/{$conversation->id}", ['body' => $body]);
    }

    public function test_a_member_can_start_a_conversation_from_a_profile(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();

        $this->actingAs($alpha)->get("/devs/{$ibrahim->username}")->assertSee('Envoyer un message');

        $response = $this->actingAs($alpha)->post("/devs/{$ibrahim->username}/message");

        $conversation = Conversation::between($alpha, $ibrahim);
        $response->assertRedirect(route('messages.show', $conversation));
        $this->assertSame(1, Conversation::count());

        // Contacting the same member again reuses the conversation, in either direction.
        $this->actingAs($ibrahim)->post("/devs/{$alpha->username}/message")->assertRedirect(route('messages.show', $conversation));
        $this->assertSame(1, Conversation::count());
    }

    public function test_members_cannot_message_themselves_or_hidden_profiles(): void
    {
        $alpha = User::factory()->create();

        $this->actingAs($alpha)->post("/devs/{$alpha->username}/message")->assertForbidden();
        $this->actingAs($alpha)->post('/devs/'.User::factory()->create(['is_public' => false])->username.'/message')->assertForbidden();
        $this->actingAs($alpha)->post('/devs/'.User::factory()->suspended()->create()->username.'/message')->assertForbidden();
        $this->actingAs($alpha)->post('/devs/'.User::factory()->unverified()->create()->username.'/message')->assertForbidden();

        $this->assertSame(0, Conversation::count());
    }

    public function test_guests_and_unverified_members_cannot_use_the_messaging(): void
    {
        $this->get('/messages')->assertRedirect('/login');

        $this->actingAs(User::factory()->unverified()->create())
            ->get('/messages')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_message_is_saved_and_pushed_in_real_time_to_the_recipient(): void
    {
        Event::fake([MessageSent::class]);
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);

        $this->send($alpha, $conversation, "  Salut Ibrahim,\ntu es dispo ?  ")
            ->assertCreated()
            ->assertJsonPath('message.body', "Salut Ibrahim,\ntu es dispo ?")
            ->assertJsonPath('message.sender_id', $alpha->id);

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'sender_id' => $alpha->id]);
        $this->assertNotNull($conversation->fresh()->last_message_at);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation, $ibrahim) {
            $channels = collect($event->broadcastOn())->map->name->all();
            $payload = $event->broadcastWith();

            return $channels === ["private-conversations.{$conversation->id}", "private-App.Models.User.{$ibrahim->id}"]
                && $payload['unread_total'] === 1
                && $payload['conversation']['unread'] === 1
                && $payload['conversation']['other']['name'] === $event->message->sender->name;
        });
    }

    public function test_empty_and_too_long_messages_are_rejected(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);

        $this->send($alpha, $conversation, '   ')->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->send($alpha, $conversation, str_repeat('a', 2001))->assertUnprocessable()->assertJsonValidationErrors('body');

        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_only_the_two_members_can_read_or_write_in_a_conversation(): void
    {
        [$alpha, $ibrahim, $outsider] = User::factory(3)->create();
        $conversation = Conversation::between($alpha, $ibrahim);
        $this->send($alpha, $conversation, 'Message privé');

        $this->actingAs($outsider)->get("/messages/{$conversation->id}")->assertForbidden();
        $this->actingAs($outsider)->getJson("/messages/{$conversation->id}/poll")->assertForbidden();
        $this->actingAs($outsider)->postJson("/messages/{$conversation->id}/read")->assertForbidden();
        $this->send($outsider, $conversation)->assertForbidden();

        $this->actingAs($outsider)->get('/messages')->assertOk()->assertDontSee('Message privé');
    }

    public function test_opening_a_conversation_marks_received_messages_as_read(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);
        $this->send($alpha, $conversation, 'Premier');
        $this->send($alpha, $conversation, 'Second');

        $this->assertSame(2, $ibrahim->unreadMessagesCount());
        $this->assertSame(0, $alpha->unreadMessagesCount());

        Event::fake([MessagesRead::class]);

        $this->actingAs($ibrahim)->get("/messages/{$conversation->id}")
            ->assertOk()
            ->assertViewHas('messages', fn ($messages) => $messages->pluck('body')->all() === ['Premier', 'Second']);

        $this->assertSame(0, $ibrahim->unreadMessagesCount());
        Event::assertDispatched(MessagesRead::class, fn (MessagesRead $event) => $event->reader->is($ibrahim)
            && $event->lastReadId === $conversation->messages()->max('id'));

        // Alpha now sees "Lu" under the last message.
        $this->actingAs($alpha)->get("/messages/{$conversation->id}")
            ->assertViewHas('readUpTo', $conversation->messages()->max('id'));
    }

    public function test_the_read_endpoint_returns_the_new_unread_total(): void
    {
        [$alpha, $ibrahim, $mamadou] = User::factory(3)->create();
        $withAlpha = Conversation::between($alpha, $ibrahim);
        $withMamadou = Conversation::between($mamadou, $ibrahim);
        $this->send($alpha, $withAlpha);
        $this->send($mamadou, $withMamadou);

        $this->actingAs($ibrahim)->postJson("/messages/{$withAlpha->id}/read")
            ->assertOk()
            ->assertExactJson(['unread_total' => 1]);
    }

    public function test_the_recipient_is_notified_once_until_they_read_the_conversation(): void
    {
        Notification::fake();
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);

        $this->send($alpha, $conversation, 'Un');
        $this->send($alpha, $conversation, 'Deux');
        Notification::assertSentToTimes($ibrahim, NewMessage::class, 1);

        $this->actingAs($ibrahim)->get("/messages/{$conversation->id}");
        $this->send($alpha, $conversation, 'Trois');
        Notification::assertSentToTimes($ibrahim, NewMessage::class, 2);
        Notification::assertNotSentTo($alpha, NewMessage::class);
    }

    public function test_the_polling_fallback_returns_only_newer_messages(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);
        $firstId = $this->send($alpha, $conversation, 'Ancien')->json('message.id');
        $this->send($ibrahim, $conversation, 'Nouveau');

        $this->actingAs($alpha)->getJson("/messages/{$conversation->id}/poll?after={$firstId}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Nouveau')
            ->assertJsonPath('read_up_to', 0);
    }

    public function test_the_inbox_lists_conversations_with_messages_most_recent_first(): void
    {
        [$alpha, $ibrahim, $mamadou, $silent] = User::factory(4)->create();
        $older = Conversation::between($alpha, $ibrahim);
        $newer = Conversation::between($alpha, $mamadou);
        Conversation::between($alpha, $silent); // opened but never used: not listed

        $this->send($ibrahim, $older, 'Message plus ancien');
        $this->travel(1)->minute();
        $this->send($mamadou, $newer, 'Message plus récent');

        $this->actingAs($alpha)->get('/messages')
            ->assertOk()
            ->assertViewHas('conversations', fn ($conversations) => $conversations->pluck('id')->all() === [$newer->id, $older->id]
                && $conversations->first()['unread'] === 1
                && $conversations->first()['last']['body'] === 'Message plus récent');

        $this->actingAs($alpha)->getJson('/notifications/poll')->assertJsonPath('unread_messages', 2);
    }

    public function test_a_suspended_member_can_no_longer_receive_replies(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);
        $this->send($ibrahim, $conversation, 'Avant la suspension');

        $ibrahim->forceFill(['suspended_at' => now()])->save();

        $this->send($alpha, $conversation)->assertForbidden();
        $this->actingAs($alpha)->get("/messages/{$conversation->id}")
            ->assertOk()
            ->assertSee("Ce membre n'est plus joignable", false);
    }

    public function test_only_the_two_members_can_listen_to_a_conversation_channel(): void
    {
        $this->useReverbBroadcaster();
        require base_path('routes/channels.php');

        [$alpha, $ibrahim, $outsider] = User::factory(3)->create();
        $conversation = Conversation::between($alpha, $ibrahim);
        $request = ['socket_id' => '1234.5678', 'channel_name' => "private-conversations.{$conversation->id}"];

        $this->actingAs($alpha)->post('/broadcasting/auth', $request)->assertOk()->assertJsonStructure(['auth']);
        $this->actingAs($outsider)->post('/broadcasting/auth', $request)->assertForbidden();
        $this->actingAs($outsider)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$alpha->id}"])
            ->assertForbidden();
    }

    public function test_sending_still_works_when_the_websocket_server_is_down(): void
    {
        $this->useReverbBroadcaster();
        [$alpha, $ibrahim] = User::factory(2)->create();
        $conversation = Conversation::between($alpha, $ibrahim);

        $this->send($alpha, $conversation, 'Toujours envoyé')->assertCreated();

        $this->assertSame(1, $conversation->messages()->count());
    }

    /**
     * Points broadcasting at a Reverb server on a closed port: authorization works locally,
     * every broadcast fails like it would with the WebSocket server stopped.
     */
    private function useReverbBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'key',
                'secret' => 'secret',
                'app_id' => '1',
                'options' => ['host' => '127.0.0.1', 'port' => 1, 'scheme' => 'http', 'useTLS' => false],
            ],
        ]);
    }
}
