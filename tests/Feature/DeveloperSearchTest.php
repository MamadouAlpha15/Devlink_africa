<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeveloperSearchTest extends TestCase
{
    use RefreshDatabase;

    private Country $guinea;

    private Country $senegal;

    private Skill $laravel;

    private Skill $flutter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guinea = Country::factory()->create(['code' => 'GN', 'name' => 'Guinée', 'slug' => 'guinee']);
        $this->senegal = Country::factory()->create(['code' => 'SN', 'name' => 'Sénégal', 'slug' => 'senegal']);
        $this->laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
        $this->flutter = Skill::factory()->create(['name' => 'Flutter', 'slug' => 'flutter', 'category' => 'mobile']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function developer(string $name, array $attributes = [], array $skills = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => $name,
            'country_id' => $this->guinea->id,
            'city' => 'Conakry',
            'bio' => 'Développeur passionné.',
            'availability' => 'available',
            'show_availability' => true,
            'is_public' => true,
            'profile_completed_at' => now(),
        ], $attributes));

        foreach ($skills as $skill) {
            $user->skills()->attach($skill->id, ['level' => 'advanced']);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function names(TestResponse $response): array
    {
        return $response->viewData('developers')->pluck('name')->all();
    }

    public function test_the_search_page_lists_developers_for_guests(): void
    {
        $this->developer('Alpha Keita');

        $response = $this->get('/devs')->assertOk()->assertSee('Alpha Keita');

        $this->assertSame(['Alpha Keita'], $this->names($response));
    }

    public function test_only_listed_developers_appear(): void
    {
        $this->developer('Visible Dev');
        $this->developer('Private Dev', ['is_public' => false]);
        $this->developer('Suspended Dev', ['suspended_at' => now()]);
        $this->developer('Incomplete Dev', ['profile_completed_at' => null]);
        $this->developer('Unverified Dev', ['email_verified_at' => null]);

        $this->assertSame(['Visible Dev'], $this->names($this->get('/devs')));
    }

    public function test_the_cahier_example_laravel_guinea_available(): void
    {
        $this->developer('Alpha Keita', [], [$this->laravel]);
        $this->developer('Busy Laravel', ['availability' => 'busy'], [$this->laravel]);
        $this->developer('Senegal Laravel', ['country_id' => $this->senegal->id], [$this->laravel]);
        $this->developer('Guinea Flutter', [], [$this->flutter]);

        $response = $this->get('/devs?skill=laravel&country=guinee&availability=available');

        $this->assertSame(['Alpha Keita'], $this->names($response));
    }

    public function test_keyword_matches_name_city_bio_skill_and_country(): void
    {
        $this->developer('Ibrahim Sow', ['country_id' => $this->senegal->id, 'city' => 'Dakar'], [$this->flutter]);
        $this->developer('Alpha Keita', ['bio' => 'Expert en paiement mobile money.'], [$this->laravel]);

        $this->assertSame(['Ibrahim Sow'], $this->names($this->get('/devs?q=ibrahim')));
        $this->assertSame(['Ibrahim Sow'], $this->names($this->get('/devs?q=dakar')));
        $this->assertSame(['Ibrahim Sow'], $this->names($this->get('/devs?q=flutter')));
        $this->assertSame(['Ibrahim Sow'], $this->names($this->get('/devs?q=Sénégal')));
        $this->assertSame(['Alpha Keita'], $this->names($this->get('/devs?q=mobile+money')));
    }

    public function test_every_keyword_must_match(): void
    {
        $this->developer('Alpha Keita', [], [$this->laravel]);
        $this->developer('Ibrahim Sow', ['city' => 'Dakar'], [$this->laravel]);

        $this->assertSame(['Alpha Keita'], $this->names($this->get('/devs?q=laravel+conakry')));
    }

    public function test_city_level_and_profile_type_filters(): void
    {
        $this->developer('Alpha Keita', ['city' => 'Conakry', 'experience_level' => 'senior', 'profile_type' => 'developer']);
        $this->developer('Mamadou Diallo', ['city' => 'Labé', 'experience_level' => 'junior', 'profile_type' => 'designer']);

        $this->assertSame(['Mamadou Diallo'], $this->names($this->get('/devs?city=lab')));
        $this->assertSame(['Alpha Keita'], $this->names($this->get('/devs?level=senior')));
        $this->assertSame(['Mamadou Diallo'], $this->names($this->get('/devs?type=designer')));
    }

    public function test_hidden_availability_is_never_used_to_filter(): void
    {
        $this->developer('Shown', ['availability' => 'available', 'show_availability' => true]);
        $this->developer('Hidden', ['availability' => 'available', 'show_availability' => false]);

        $this->assertSame(['Shown'], $this->names($this->get('/devs?availability=available')));
    }

    public function test_results_are_sorted_by_devscore_by_default(): void
    {
        $this->developer('Low', ['devscore' => 10]);
        $this->developer('High', ['devscore' => 900]);
        $this->developer('Middle', ['devscore' => 300]);

        $this->assertSame(['High', 'Middle', 'Low'], $this->names($this->get('/devs')));
        $this->assertSame(['High', 'Low', 'Middle'], $this->names($this->get('/devs?sort=name')));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->developer('Alpha Keita');

        $response = $this->get('/devs?level=god&availability=x&type[]=hack&sort=drop&country[]=1')->assertOk();

        $this->assertSame(['Alpha Keita'], $this->names($response));
    }

    public function test_results_are_paginated_and_keep_the_filters(): void
    {
        foreach (range(1, 14) as $i) {
            $this->developer("Dev $i", [], [$this->laravel]);
        }

        $page1 = $this->get('/devs?skill=laravel');
        $this->assertCount(12, $this->names($page1));
        $page1->assertSee('skill=laravel&amp;page=2', false);

        $this->assertCount(2, $this->names($this->get('/devs?skill=laravel&page=2')));
    }

    public function test_developers_are_counted_per_country(): void
    {
        $this->developer('A');
        $this->developer('B');
        $this->developer('C', ['country_id' => $this->senegal->id]);
        $this->developer('Private', ['is_public' => false]);

        $counts = $this->get('/devs')->viewData('topCountries')->pluck('developers_count', 'name')->all();

        $this->assertSame(['Guinée' => 2, 'Sénégal' => 1], $counts);
    }
}
