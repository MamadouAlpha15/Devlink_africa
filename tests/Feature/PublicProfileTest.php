<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_see_a_public_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Alpha Keita',
            'country_id' => Country::factory()->create(['code' => 'GN', 'name' => 'Guinée'])->id,
            'city' => 'Conakry',
        ]);

        $this->get("/devs/{$user->username}")
            ->assertOk()
            ->assertSee('Alpha Keita')
            ->assertSee('Guinée')
            ->assertSee('Conakry')
            ->assertSee('Connexion');
    }

    public function test_email_and_availability_respect_privacy_settings(): void
    {
        $hidden = User::factory()->create(['email' => 'hidden@example.com', 'show_email' => false, 'show_availability' => false, 'availability' => 'busy']);
        $shown = User::factory()->create(['email' => 'shown@example.com', 'show_email' => true, 'show_availability' => true, 'availability' => 'busy']);

        $this->get("/devs/{$hidden->username}")->assertDontSee('hidden@example.com')->assertDontSee('Indisponible');
        $this->get("/devs/{$shown->username}")->assertSee('shown@example.com')->assertSee('Indisponible');
    }

    public function test_private_and_suspended_profiles_are_hidden(): void
    {
        $private = User::factory()->create(['is_public' => false]);
        $suspended = User::factory()->suspended()->create();

        $this->get("/devs/{$private->username}")->assertNotFound();
        $this->get("/devs/{$suspended->username}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/devs/{$private->username}")->assertNotFound();
    }

    public function test_owners_and_admins_can_see_a_private_profile(): void
    {
        $private = User::factory()->create(['is_public' => false]);

        $this->actingAs($private)->get("/devs/{$private->username}")
            ->assertOk()
            ->assertSee('Votre profil est privé');

        $this->actingAs(User::factory()->admin()->create())->get("/devs/{$private->username}")->assertOk();
    }

    public function test_unverified_profiles_are_hidden_until_the_email_is_verified(): void
    {
        $unverified = User::factory()->unverified()->create();

        $this->get("/devs/{$unverified->username}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/devs/{$unverified->username}")->assertNotFound();
        $this->actingAs($unverified)->get("/devs/{$unverified->username}")->assertOk();

        $unverified->markEmailAsVerified();

        $this->actingAs(User::factory()->create())->get("/devs/{$unverified->username}")->assertOk();
    }

    public function test_unknown_usernames_return_404(): void
    {
        $this->get('/devs/personne')->assertNotFound();
    }
}
