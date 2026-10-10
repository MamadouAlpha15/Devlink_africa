<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_follow_and_unfollow_another(): void
    {
        [$alpha, $ibrahim] = User::factory(2)->create();

        $this->actingAs($alpha)->post("/devs/{$ibrahim->username}/follow")->assertRedirect();
        $this->assertTrue($alpha->following()->whereKey($ibrahim->id)->exists());
        $this->assertSame(1, $ibrahim->followers()->count());

        // Following twice does not create a duplicate.
        $this->actingAs($alpha)->post("/devs/{$ibrahim->username}/follow");
        $this->assertSame(1, $ibrahim->followers()->count());

        $this->actingAs($alpha)->delete("/devs/{$ibrahim->username}/follow");
        $this->assertSame(0, $ibrahim->followers()->count());
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $alpha = User::factory()->create();

        $this->actingAs($alpha)->post("/devs/{$alpha->username}/follow")->assertForbidden();
    }

    public function test_a_private_profile_cannot_be_followed(): void
    {
        $alpha = User::factory()->create();
        $private = User::factory()->create(['is_public' => false]);

        $this->actingAs($alpha)->post("/devs/{$private->username}/follow")->assertForbidden();
    }

    public function test_guests_and_unverified_users_cannot_follow(): void
    {
        $ibrahim = User::factory()->create();

        $this->post("/devs/{$ibrahim->username}/follow")->assertRedirect('/login');
        $this->actingAs(User::factory()->unverified()->create())
            ->post("/devs/{$ibrahim->username}/follow")
            ->assertRedirect('/verify-email');
    }
}
