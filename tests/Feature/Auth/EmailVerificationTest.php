<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_link_works_without_being_logged_in(): void
    {
        // E.g. registered on a computer, email opened on a phone.
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Votre adresse email est vérifiée ! Vous pouvez maintenant vous connecter.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();

        $this->get(route('login'))->assertSee('Votre adresse email est vérifiée');
    }

    public function test_a_guest_cannot_verify_with_a_forged_or_expired_link(): void
    {
        $user = User::factory()->unverified()->create();

        // Unsigned link: rejected.
        $this->get("/verify-email/{$user->id}/".sha1($user->email))->assertForbidden();

        // Signed but for another address: rejected.
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.com')]))
            ->assertForbidden();

        // Expired: rejected.
        $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_members_see_a_reminder_banner_and_can_resend_the_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/profile/developer')
            ->assertOk()
            ->assertSee('Vérifiez votre e-mail pour apparaître sur DevLink Africa.');

        $this->actingAs($user)
            ->from('/profile/developer')
            ->post(route('verification.send'))
            ->assertRedirect('/profile/developer');

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)->get('/profile/developer')
            ->assertSee("Un nouveau lien de vérification vient d'être envoyé", false)
            ->assertDontSee('verification-link-sent');
    }

    public function test_verified_members_do_not_see_the_reminder_banner(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile/developer')
            ->assertOk()
            ->assertDontSee('Vérifiez votre e-mail pour apparaître sur DevLink Africa.');
    }
}
