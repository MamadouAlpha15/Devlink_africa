<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    private Country $guinea;

    private Country $senegal;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 12:00:00');
        $this->guinea = Country::factory()->create(['code' => 'GN', 'name' => 'Guinée', 'slug' => 'guinee']);
        $this->senegal = Country::factory()->create(['code' => 'SN', 'name' => 'Sénégal', 'slug' => 'senegal']);
    }

    /**
     * @param  array<string, int>  $points  date => points
     */
    private function developer(string $name, Country $country, array $points, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => $name,
            'country_id' => $country->id,
            'profile_completed_at' => now(),
            'devscore' => array_sum($points),
        ], $attributes));

        foreach ($points as $date => $value) {
            $user->pointTransactions()->create(['action' => 'project_created', 'points' => $value])
                ->forceFill(['created_at' => Carbon::parse($date)])->save();
        }

        return $user;
    }

    /**
     * @return array<string, int>
     */
    private function ranking(TestResponse $response): array
    {
        return $response->viewData('developers')->mapWithKeys(fn (User $user) => [$user->name => (int) $user->points])->all();
    }

    private function seedCommunity(): void
    {
        // Cahier example: 1. Alpha (Guinée) 942 — 2. Ibrahim (Sénégal) 921 — 3. Mohamed 890.
        $this->developer('Alpha', $this->guinea, ['2026-10-02' => 500, '2025-06-01' => 442]);
        $this->developer('Ibrahim', $this->senegal, ['2026-10-05' => 900, '2026-01-10' => 21]);
        $this->developer('Mamadou', $this->guinea, ['2026-03-01' => 890]);
    }

    public function test_the_all_time_ranking_of_africa(): void
    {
        $this->seedCommunity();

        $this->assertSame(['Alpha' => 942, 'Ibrahim' => 921, 'Mamadou' => 890], $this->ranking($this->get('/ranking')->assertOk()));
    }

    public function test_the_ranking_of_one_country(): void
    {
        $this->seedCommunity();

        $this->assertSame(['Alpha' => 942, 'Mamadou' => 890], $this->ranking($this->get('/ranking?country=guinee')));
    }

    public function test_the_ranking_of_a_month_and_of_a_year(): void
    {
        $this->seedCommunity();

        $this->assertSame(['Ibrahim' => 900, 'Alpha' => 500], $this->ranking($this->get('/ranking?period=month&month=2026-10')));
        $this->assertSame(['Ibrahim' => 921, 'Mamadou' => 890, 'Alpha' => 500], $this->ranking($this->get('/ranking?period=year&year=2026')));
        $this->assertSame(['Alpha' => 442], $this->ranking($this->get('/ranking?period=year&year=2025')));
    }

    public function test_only_listed_developers_are_ranked(): void
    {
        $this->seedCommunity();
        $this->developer('Private', $this->guinea, ['2026-10-01' => 5000], ['is_public' => false]);
        $this->developer('Suspended', $this->guinea, ['2026-10-01' => 5000], ['suspended_at' => now()]);

        $this->assertSame(['Alpha', 'Ibrahim', 'Mamadou'], array_keys($this->ranking($this->get('/ranking'))));
    }

    public function test_the_viewer_sees_their_rank(): void
    {
        $this->seedCommunity();
        $mamadou = User::where('name', 'Mamadou')->first();

        $this->actingAs($mamadou)->get('/ranking')->assertViewHas('myRank', 3);
        $this->actingAs($mamadou)->get('/ranking?country=guinee')->assertViewHas('myRank', 2);
        $this->actingAs($mamadou)->get('/ranking?period=month&month=2026-10')->assertViewHas('myRank', null);
    }

    public function test_invalid_or_future_periods_fall_back_to_the_current_one(): void
    {
        $this->seedCommunity();

        $this->get('/ranking?period=month&month=2099-01')->assertOk()->assertViewHas('periodLabel', 'Octobre 2026');
        $this->get('/ranking?period=year&year=abc')->assertOk()->assertViewHas('periodLabel', '2026');
        $this->get('/ranking?period=hack&country[]=x')->assertOk()->assertViewHas('periodLabel', 'Depuis toujours');
    }

    public function test_my_devscore_page_shows_breakdown_ranks_and_badges(): void
    {
        $this->get('/devscore')->assertRedirect('/login');

        $this->seedCommunity();
        $alpha = User::where('name', 'Alpha')->first();
        $this->seed(BadgeSeeder::class);

        $response = $this->actingAs($alpha)->get('/devscore')->assertOk()
            ->assertSee('942 points')
            ->assertSee('Projet créé')
            ->assertSee('Premier projet');

        $this->assertSame(['africa' => 1, 'country' => 1, 'month' => 2], $response->viewData('ranks'));
        $this->assertSame(942, $response->viewData('breakdown')->firstWhere('action.value', 'project_created')['total']);
    }
}
