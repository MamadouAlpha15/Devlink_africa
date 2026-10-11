<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_save_a_developer_a_resource_and_a_project(): void
    {
        $me = User::factory()->create();
        $ibrahim = User::factory()->create(['name' => 'Ibrahim Sow']);
        $resource = Resource::factory()->create(['title' => 'Wave API Client']);
        $project = Project::factory()->create(['title' => 'AfricaDelivery']);

        $this->actingAs($me)->post("/favorites/developer/{$ibrahim->id}")->assertSessionHas('status');
        $this->actingAs($me)->post("/favorites/resource/{$resource->id}");
        $this->actingAs($me)->post("/favorites/project/{$project->id}");
        $this->actingAs($me)->post("/favorites/project/{$project->id}"); // twice: no duplicate

        $this->assertSame(3, $me->favorites()->count());
        $this->assertTrue($me->hasFavorited($ibrahim));
        $this->assertDatabaseHas('favorites', ['user_id' => $me->id, 'favoritable_type' => 'project', 'favoritable_id' => $project->id]);

        $page = $this->actingAs($me)->get('/favorites')->assertOk()
            ->assertSee('Ibrahim Sow')->assertSee('Wave API Client')->assertSee('AfricaDelivery');
        $this->assertCount(1, $page->viewData('developers'));
        $this->assertCount(1, $page->viewData('resources'));
        $this->assertCount(1, $page->viewData('projects'));
    }

    public function test_a_favorite_can_be_removed(): void
    {
        $me = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($me)->post("/favorites/project/{$project->id}");

        $this->actingAs($me)->delete("/favorites/project/{$project->id}");

        $this->assertFalse($me->hasFavorited($project));
    }

    public function test_nobody_can_save_themselves_or_hidden_content(): void
    {
        $me = User::factory()->create();
        $private = User::factory()->create(['is_public' => false]);
        $pending = Resource::factory()->pending()->create();

        $this->actingAs($me)->post("/favorites/developer/{$me->id}")->assertStatus(422);
        $this->actingAs($me)->post("/favorites/developer/{$private->id}")->assertNotFound();
        $this->actingAs($me)->post("/favorites/resource/{$pending->id}")->assertNotFound();
        $this->actingAs($me)->post('/favorites/developer/99999')->assertNotFound();
        $this->actingAs($me)->post('/favorites/opportunity/1')->assertNotFound();

        $this->assertSame(0, $me->favorites()->count());
    }

    public function test_deleted_or_hidden_items_disappear_from_my_favorites(): void
    {
        $me = User::factory()->create();
        $project = Project::factory()->create();
        $developer = User::factory()->create();
        $this->actingAs($me)->post("/favorites/project/{$project->id}");
        $this->actingAs($me)->post("/favorites/developer/{$developer->id}");

        $project->delete();
        $developer->update(['is_public' => false]);

        $page = $this->actingAs($me)->get('/favorites')->assertOk();
        $this->assertCount(0, $page->viewData('projects'));
        $this->assertCount(0, $page->viewData('developers'));
    }

    public function test_the_favorite_button_shows_on_detail_pages(): void
    {
        $me = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($me)->get("/projects/{$project->slug}")->assertSee('Ajouter aux favoris');
        $this->actingAs($me)->post("/favorites/project/{$project->id}");
        $this->actingAs($me)->get("/projects/{$project->slug}")->assertSee('Dans mes favoris');

        // No button on my own profile.
        $this->actingAs($me)->get("/devs/{$me->username}")->assertDontSee('Ajouter aux favoris');
    }

    public function test_favorites_are_reserved_to_members(): void
    {
        $this->get('/favorites')->assertRedirect('/login');
        $this->post('/favorites/project/1')->assertRedirect('/login');
    }
}
