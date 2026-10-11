<?php

namespace Tests\Feature;

use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use App\Services\ResourceLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Orange Money pour Laravel',
            'description' => 'Package Laravel pour encaisser des paiements Orange Money.',
            'type' => 'package',
            'tags' => 'paiement, Mobile Money, mobile money',
            'github_url' => 'https://github.com/alpha/orange-money',
        ], $overrides);
    }

    private function publish(User $author, array $overrides = []): Resource
    {
        return app(ResourceLibrary::class)->publish($author, array_merge([
            'title' => 'Wave API Client',
            'description' => 'Client Dart pour Wave.',
            'type' => ResourceType::Api,
        ], $overrides));
    }

    public function test_a_member_can_publish_a_resource_with_image_file_skills_and_tags(): void
    {
        $author = User::factory()->create(['devscore' => 0]);
        $laravel = Skill::factory()->create(['name' => 'Laravel']);

        $response = $this->actingAs($author)->post('/resources', $this->resourceData([
            'skills' => [$laravel->id],
            'image' => UploadedFile::fake()->image('cover.png'),
            'file' => UploadedFile::fake()->create('orange-money.zip', 300, 'application/zip'),
        ]));

        $resource = Resource::firstOrFail();
        $response->assertRedirect(route('resources.show', $resource));

        $this->assertSame(ResourceStatus::Approved, $resource->status);
        $this->assertSame(['Laravel'], $resource->skills->pluck('name')->all());
        // "Mobile Money" and "mobile money" are the same tag.
        $this->assertSame(['paiement', 'Mobile Money'], $resource->tags->pluck('name')->all());
        Storage::disk('public')->assertExists($resource->image_path);
        Storage::disk('local')->assertExists($resource->file_path);
        $this->assertStringEndsWith('.zip', $resource->file_path);

        $this->assertSame(30, $author->fresh()->devscore);
        $this->assertDatabaseHas('activities', ['user_id' => $author->id, 'type' => 'resource_published']);
    }

    public function test_publication_is_validated(): void
    {
        $this->actingAs(User::factory()->create())->post('/resources', $this->resourceData([
            'title' => '',
            'type' => 'virus',
            'github_url' => 'http://not-https.com',
            'tags' => 'a, '.implode(', ', range(1, 10)).'0',
            'file' => UploadedFile::fake()->create('script.exe', 10),
        ]))->assertSessionHasErrors(['title', 'type', 'github_url', 'tags', 'file']);

        $this->assertDatabaseCount('resources', 0);
    }

    public function test_guests_and_unverified_users_cannot_publish(): void
    {
        $this->get('/resources/create')->assertRedirect('/login');
        $this->actingAs(User::factory()->unverified()->create())->get('/resources/create')->assertRedirect('/verify-email');
    }

    public function test_resources_are_public_but_pending_ones_are_hidden(): void
    {
        $author = User::factory()->create();
        $published = $this->publish($author, ['title' => 'Publiée']);
        $pending = Resource::factory()->pending()->create(['user_id' => $author->id, 'title' => 'En attente']);

        $this->get('/resources')->assertOk()->assertSee('Publiée')->assertDontSee('En attente');
        $this->get("/resources/{$published->slug}")->assertOk();
        $this->get("/resources/{$pending->slug}")->assertNotFound();
        $this->actingAs($author)->get("/resources/{$pending->slug}")->assertOk()->assertSee('En attente');
    }

    public function test_the_file_can_be_downloaded_and_downloads_are_counted(): void
    {
        $resource = app(ResourceLibrary::class)->publish(User::factory()->create(), [
            'title' => 'Script de sauvegarde MySQL',
            'description' => 'Sauvegarde.',
            'type' => ResourceType::Script,
        ], file: UploadedFile::fake()->createWithContent('backup.md', '# Backup'));

        $this->get("/resources/{$resource->slug}/download")
            ->assertOk()
            ->assertDownload('script-de-sauvegarde-mysql.md');

        $this->assertSame(1, $resource->fresh()->downloads_count);

        $withoutFile = $this->publish(User::factory()->create());
        $this->get("/resources/{$withoutFile->slug}/download")->assertNotFound();
    }

    public function test_only_the_author_can_edit_or_delete(): void
    {
        $resource = $this->publish($author = User::factory()->create());
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/resources/{$resource->slug}/edit")->assertForbidden();
        $this->actingAs($stranger)->delete("/resources/{$resource->slug}")->assertForbidden();

        $this->actingAs($author)->put("/resources/{$resource->slug}", $this->resourceData(['title' => 'Nouveau titre', 'tags' => 'api']))
            ->assertRedirect();
        $this->assertSame('Nouveau titre', $resource->fresh()->title);
        $this->assertSame(['api'], $resource->fresh()->tags->pluck('name')->all());

        $this->actingAs($author)->delete("/resources/{$resource->slug}")->assertRedirect('/resources');
        $this->assertSoftDeleted($resource);
    }

    public function test_likes_give_points_to_the_author_and_unlikes_take_them_back(): void
    {
        $author = User::factory()->create(['devscore' => 0]);
        $resource = $this->publish($author); // +30
        $fan = User::factory()->create();

        $this->actingAs($fan)->post("/resources/{$resource->slug}/like");
        $this->actingAs($fan)->post("/resources/{$resource->slug}/like"); // second like ignored
        $this->assertSame(1, $resource->fresh()->likes_count);
        $this->assertSame(35, $author->fresh()->devscore);

        $this->actingAs($fan)->delete("/resources/{$resource->slug}/like");
        $this->assertSame(0, $resource->fresh()->likes_count);
        $this->assertSame(30, $author->fresh()->devscore);

        // Like / unlike again: no way to farm points.
        $this->actingAs($fan)->post("/resources/{$resource->slug}/like");
        $this->actingAs($fan)->delete("/resources/{$resource->slug}/like");
        $this->assertSame(30, $author->fresh()->devscore);
    }

    public function test_authors_cannot_like_or_rate_their_own_resource(): void
    {
        $resource = $this->publish($author = User::factory()->create());

        $this->actingAs($author)->post("/resources/{$resource->slug}/like")->assertForbidden();
        $this->actingAs($author)->post("/resources/{$resource->slug}/reviews", ['rating' => 5])->assertForbidden();
    }

    public function test_members_rate_from_one_to_five_and_the_average_is_kept(): void
    {
        $resource = $this->publish(User::factory()->create());
        [$a, $b] = User::factory(2)->create();

        $this->actingAs($a)->post("/resources/{$resource->slug}/reviews", ['rating' => 5, 'comment' => 'Très bonne API pour Laravel.']);
        $this->actingAs($b)->post("/resources/{$resource->slug}/reviews", ['rating' => 4]);
        $this->assertSame(4.5, $resource->fresh()->rating_avg);

        // Rating again replaces the previous rating.
        $this->actingAs($b)->post("/resources/{$resource->slug}/reviews", ['rating' => 3]);
        $this->assertSame(2, $resource->reviews()->count());
        $this->assertSame(4.0, $resource->fresh()->rating_avg);

        $this->actingAs($a)->post("/resources/{$resource->slug}/reviews", ['rating' => 6])->assertSessionHasErrors('rating');

        $this->actingAs($b)->delete("/resources/{$resource->slug}/reviews");
        $this->assertSame(5.0, $resource->fresh()->rating_avg);

        $this->get("/resources/{$resource->slug}")->assertSee('Très bonne API pour Laravel.');
    }

    public function test_comments_can_be_posted_and_deleted_by_their_author_or_the_resource_author(): void
    {
        $resource = $this->publish($author = User::factory()->create());
        [$commenter, $stranger] = User::factory(2)->create();

        $this->post("/resources/{$resource->slug}/comments", ['body' => 'anonyme'])->assertRedirect('/login');

        $this->actingAs($commenter)->post("/resources/{$resource->slug}/comments", ['body' => 'Merci pour le partage !']);
        $comment = $resource->comments()->firstOrFail();
        $this->get("/resources/{$resource->slug}")->assertSee('Merci pour le partage !');

        $this->actingAs($stranger)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->actingAs($author)->delete("/comments/{$comment->id}");
        $this->assertSoftDeleted($comment);
    }

    public function test_resources_can_be_searched_and_filtered(): void
    {
        $author = User::factory()->create();
        $laravel = Skill::factory()->create(['slug' => 'laravel']);

        $orange = app(ResourceLibrary::class)->publish($author, [
            'title' => 'Orange Money pour Laravel', 'description' => 'Paiement mobile.', 'type' => ResourceType::Package,
            'skills' => [$laravel->id], 'tags' => ['mobile money'],
        ]);
        $uiKit = $this->publish($author, ['title' => 'Flutter Delivery UI Kit', 'description' => 'Écrans de livraison.', 'type' => ResourceType::UiKit]);
        $uiKit->forceFill(['downloads_count' => 50])->save();

        $titles = fn (string $uri) => $this->get($uri)->assertOk()->viewData('resources')->pluck('title')->all();

        $this->assertSame(['Orange Money pour Laravel'], $titles('/resources?q=paiement'));
        $this->assertSame(['Flutter Delivery UI Kit'], $titles('/resources?type=ui_kit'));
        $this->assertSame(['Orange Money pour Laravel'], $titles('/resources?skill=laravel'));
        $this->assertSame(['Orange Money pour Laravel'], $titles('/resources?tag=mobile-money'));
        $this->assertSame(['Flutter Delivery UI Kit', 'Orange Money pour Laravel'], $titles('/resources?sort=downloads'));
        $this->assertCount(2, $titles('/resources?type=x&sort=y&skill[]=z'));
    }
}
