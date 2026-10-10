<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Enums\ProfileType;
use App\Models\Country;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeveloperProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Alpha',
            'last_name' => 'Keita',
            'country_id' => Country::factory()->create()->id,
            'city' => 'Conakry',
            'bio' => 'Développeur Laravel à Conakry.',
            'profile_type' => ProfileType::Developer->value,
            'experience_level' => 'senior',
            'availability' => Availability::Available->value,
            'github_url' => 'https://github.com/alpha',
            'is_public' => '1',
            'show_email' => '0',
            'show_availability' => '1',
            'notify_by_email' => '1',
            'notify_on_platform' => '1',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/profile/developer')->assertRedirect('/login');
    }

    public function test_the_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile/developer')
            ->assertOk()
            ->assertSee('Mon profil développeur');
    }

    public function test_the_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile/developer', $this->validData(['show_email' => '1', 'is_public' => '0']))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/developer');

        $user->refresh();
        $this->assertSame('Alpha Keita', $user->name);
        $this->assertSame('Conakry', $user->city);
        $this->assertSame(ProfileType::Developer, $user->profile_type);
        $this->assertTrue($user->show_email);
        $this->assertFalse($user->is_public);
    }

    public function test_invalid_data_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile/developer', $this->validData([
                'country_id' => 9999,
                'profile_type' => 'astronaut',
                'github_url' => 'not-a-url',
                'city' => '',
            ]))
            ->assertSessionHasErrors(['country_id', 'profile_type', 'github_url', 'city']);
    }

    public function test_a_photo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/developer', $this->validData([
            'avatar' => UploadedFile::fake()->image('alpha.jpg'),
        ]))->assertSessionHasNoErrors();

        $first = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($user)->put('/profile/developer', $this->validData([
            'avatar' => UploadedFile::fake()->image('alpha2.png'),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_completing_the_profile_awards_devscore_points_once(): void
    {
        $user = User::factory()->create(['devscore' => 0]);
        $skill = Skill::factory()->create();

        // Not complete yet: no skill.
        $this->actingAs($user)->put('/profile/developer', $this->validData());
        $this->assertNull($user->fresh()->profile_completed_at);
        $this->assertSame(0, $user->fresh()->devscore);

        // Adding the first skill completes the profile.
        $this->actingAs($user)->post('/profile/skills', ['skill_id' => $skill->id, 'level' => 'expert']);
        $this->assertNotNull($user->fresh()->profile_completed_at);
        $this->assertSame(20, $user->fresh()->devscore);

        // Saving again does not give the points twice.
        $this->actingAs($user)->put('/profile/developer', $this->validData());
        $this->assertSame(20, $user->fresh()->devscore);
        $this->assertDatabaseCount('point_transactions', 1);
    }

    public function test_registration_redirects_to_profile_completion(): void
    {
        $this->post('/register', [
            'name' => 'Aminata Camara',
            'email' => 'aminata@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/profile/developer');

        $this->assertSame('aminata-camara', User::first()->username);
    }
}
