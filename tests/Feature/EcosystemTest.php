<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcosystemTest extends TestCase
{
    use RefreshDatabase;

    private Country $guinea;

    private Country $senegal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guinea = Country::factory()->create(['code' => 'GN', 'name' => 'Guinée', 'slug' => 'guinee']);
        $this->senegal = Country::factory()->create(['code' => 'SN', 'name' => 'Sénégal', 'slug' => 'senegal']);
    }

    /**
     * A developer who appears in listings (public, verified, completed profile).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function developer(Country $country, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'country_id' => $country->id,
            'is_public' => true,
            'profile_completed_at' => now(),
        ], $attributes));
    }

    public function test_guests_see_the_statistics_per_country(): void
    {
        $this->developer($this->guinea);
        $this->developer($this->guinea);
        $this->developer($this->senegal);
        Project::factory()->create(['country_id' => $this->senegal->id]);
        Resource::factory()->create(['country_id' => $this->guinea->id]);

        $response = $this->get('/ecosysteme')
            ->assertOk()
            ->assertSee("L'écosystème DevLink Africa")
            ->assertSee('Guinée')
            ->assertSee('Sénégal')
            ->assertSee('Créer un compte');

        $countries = $response->viewData('countries');
        $this->assertSame(['Guinée', 'Sénégal'], $countries->pluck('name')->all());
        $this->assertSame([2, 1], $countries->pluck('developers_count')->all());
        $this->assertSame([0, 1], $countries->pluck('projects_count')->all());
        $this->assertSame([1, 0], $countries->pluck('resources_count')->all());

        $this->assertSame(3, $response->viewData('totals')['developers']);
        $this->assertSame(2, $response->viewData('totals')['countries']);
    }

    public function test_only_listed_developers_and_published_resources_are_counted(): void
    {
        $this->developer($this->guinea);
        $this->developer($this->guinea, ['email_verified_at' => null]);
        $this->developer($this->guinea, ['is_public' => false]);
        $this->developer($this->guinea, ['profile_completed_at' => null]);
        $this->developer($this->guinea, ['suspended_at' => now()]);
        Resource::factory()->pending()->create(['country_id' => $this->guinea->id]);

        $response = $this->get('/ecosysteme');

        $guinea = $response->viewData('countries')->firstWhere('slug', 'guinee');
        $this->assertSame(1, $guinea->developers_count);
        $this->assertSame(0, $guinea->resources_count);
        $this->assertSame(1, $response->viewData('totals')['developers']);
    }

    public function test_each_country_shows_its_best_developer_and_main_skills(): void
    {
        $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $flutter = Skill::factory()->create(['name' => 'Flutter', 'slug' => 'flutter']);

        $alpha = $this->developer($this->guinea, ['name' => 'Alpha Keita', 'devscore' => 942]);
        $mamadou = $this->developer($this->guinea, ['name' => 'Mamadou Diallo', 'devscore' => 300]);
        $hidden = $this->developer($this->guinea, ['devscore' => 5000, 'is_public' => false]);
        $alpha->skills()->attach($laravel->id, ['level' => 'expert']);
        $mamadou->skills()->attach([$laravel->id => ['level' => 'advanced'], $flutter->id => ['level' => 'beginner']]);
        $hidden->skills()->attach($flutter->id, ['level' => 'expert']);

        $response = $this->get('/ecosysteme')->assertSee('Alpha Keita')->assertSee('942 pts');

        $guinea = $response->viewData('countries')->firstWhere('slug', 'guinee');
        $this->assertTrue($guinea->top_developer->is($alpha));
        $this->assertSame(['Laravel', 'Flutter'], $guinea->main_skills->pluck('name')->all());

        $topSkills = $response->viewData('topSkills');
        $this->assertSame(['Laravel', 'Flutter'], $topSkills->pluck('name')->all());
        $this->assertSame([2, 1], $topSkills->pluck('developers_count')->all());
    }

    public function test_an_empty_ecosystem_shows_an_empty_state(): void
    {
        $this->get('/ecosysteme')
            ->assertOk()
            ->assertSee("L'écosystème se construit")
            ->assertViewHas('countries', fn ($countries) => $countries->isEmpty());
    }

    public function test_the_developers_page_links_to_the_ecosystem(): void
    {
        $this->developer($this->guinea);

        $this->get('/devs')->assertSee(route('ecosystem'));
    }
}
