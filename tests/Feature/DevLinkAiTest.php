<?php

namespace Tests\Feature;

use App\Ai\ClaudeCriteriaExtractor;
use App\Ai\CriteriaExtractionFailed;
use App\Ai\DeveloperMatcher;
use App\Ai\KeywordCriteriaExtractor;
use App\Ai\SearchCriteria;
use App\Enums\ProjectCategory;
use App\Enums\SkillCategory;
use App\Models\AiSearch;
use App\Models\Country;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DevLinkAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountrySeeder::class, SkillSeeder::class]);
        config(['services.anthropic.key' => null]); // tests never call the real API
    }

    /**
     * @param  array<string, string>  $skills  slug => level
     */
    private function developer(string $name, string $countryCode, array $skills, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => $name,
            'country_id' => Country::where('code', $countryCode)->value('id'),
            'availability' => 'available',
            'show_availability' => true,
            'profile_completed_at' => now(),
        ], $attributes));

        foreach ($skills as $slug => $level) {
            $user->skills()->attach(Skill::where('slug', $slug)->value('id'), ['level' => $level]);
        }

        return $user;
    }

    public function test_the_local_analyser_understands_the_cahier_example(): void
    {
        $criteria = app(KeywordCriteriaExtractor::class)
            ->extract('Je cherche un développeur Flutter en Guinée pour travailler sur une application de livraison.');

        $this->assertSame(['flutter'], $criteria->skills);
        $this->assertSame('guinee', $criteria->country);
        $this->assertSame(ProjectCategory::Logistics, $criteria->projectType);
    }

    public function test_the_local_analyser_handles_aliases_demonyms_and_areas(): void
    {
        $extractor = app(KeywordCriteriaExtractor::class);

        $criteria = $extractor->extract('Un dev React Native sénégalais pour une app de paiement mobile money');
        $this->assertSame(['react-native'], $criteria->skills);
        $this->assertSame('senegal', $criteria->country);
        $this->assertSame(ProjectCategory::Fintech, $criteria->projectType);

        $criteria = $extractor->extract('Je cherche un développeur mobile à Abidjan');
        $this->assertSame([], $criteria->skills);
        $this->assertEquals([SkillCategory::Mobile], $criteria->skillCategories);
        $this->assertSame('cote-divoire', $criteria->country);

        $this->assertSame('guinee-bissau', $extractor->extract('un développeur Node.js en Guinée-Bissau')->country);
        $this->assertSame(['node-js'], $extractor->extract('un développeur Node.js en Guinée-Bissau')->skills);
    }

    public function test_the_match_percentage_follows_the_cahier_ranking(): void
    {
        // Cahier, section 18: Ibrahim (Flutter, Guinée) > Mamadou (Flutter, Guinée) > Amadou (Mobile, Guinée).
        $ibrahim = $this->developer('Ibrahim', 'GN', ['flutter' => 'expert']);
        $this->developer('Mamadou', 'GN', ['flutter' => 'advanced']);
        $this->developer('Amadou', 'GN', ['react-native' => 'expert']);
        $this->developer('Senegal Flutter', 'SN', ['flutter' => 'expert']);
        $this->developer('Laravel Only', 'GN', ['laravel' => 'expert']);

        // Ibrahim has already worked on a delivery project.
        $project = Project::factory()->create(['category' => 'logistics']);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $ibrahim->id, 'role' => 'member', 'joined_at' => now()]);

        $results = app(DeveloperMatcher::class)->match(new SearchCriteria(
            skills: ['flutter'],
            country: 'guinee',
            projectType: ProjectCategory::Logistics,
        ));

        $this->assertSame(['Ibrahim', 'Mamadou', 'Amadou', 'Senegal Flutter'], $results->pluck('user.name')->all());
        $this->assertSame(100, $results[0]['score']);
        $this->assertSame(['Flutter'], $results[0]['matched_skills']);
        $this->assertSame(['React Native'], $results[2]['matched_skills']);
        $this->assertTrue($results[0]['score'] > $results[1]['score'] && $results[1]['score'] > $results[2]['score']);
    }

    public function test_private_suspended_and_incomplete_profiles_are_never_recommended(): void
    {
        $this->developer('Visible', 'GN', ['flutter' => 'expert']);
        $this->developer('Private', 'GN', ['flutter' => 'expert'], ['is_public' => false]);
        $this->developer('Suspended', 'GN', ['flutter' => 'expert'], ['suspended_at' => now()]);
        $this->developer('Incomplete', 'GN', ['flutter' => 'expert'], ['profile_completed_at' => null]);

        $results = app(DeveloperMatcher::class)->match(new SearchCriteria(skills: ['flutter']));

        $this->assertSame(['Visible'], $results->pluck('user.name')->all());
    }

    public function test_a_member_can_search_and_see_the_results(): void
    {
        $this->developer('Ibrahim Sow', 'GN', ['flutter' => 'expert']);
        $me = User::factory()->create();

        $response = $this->actingAs($me)->post('/ai', [
            'query' => 'Je cherche un développeur Flutter en Guinée pour une application de livraison.',
        ]);

        $search = AiSearch::firstOrFail();
        $response->assertRedirect(route('ai.show', $search));
        $this->assertSame('local', $search->criteria['engine']);
        $this->assertSame(['flutter'], $search->criteria['skills']);

        $this->actingAs($me)->get(route('ai.show', $search))
            ->assertOk()
            ->assertSee('Ibrahim Sow')
            ->assertSee('Voir le profil');
    }

    public function test_searches_are_private_and_reserved_to_members(): void
    {
        $this->get('/ai')->assertRedirect('/login');
        $this->post('/ai', ['query' => 'un développeur Flutter en Guinée'])->assertRedirect('/login');

        $search = User::factory()->create()->aiSearches()->create(['query' => 'test', 'criteria' => [], 'results' => []]);
        $this->actingAs(User::factory()->create())->get(route('ai.show', $search))->assertNotFound();
    }

    public function test_the_request_is_validated(): void
    {
        $this->actingAs(User::factory()->create())->post('/ai', ['query' => 'court'])->assertSessionHasErrors('query');
    }

    public function test_the_local_analyser_takes_over_when_claude_fails(): void
    {
        $this->developer('Ibrahim Sow', 'GN', ['flutter' => 'expert']);

        $claude = Mockery::mock(ClaudeCriteriaExtractor::class);
        $claude->shouldReceive('extract')->once()->andThrow(new CriteriaExtractionFailed('network down'));
        $this->app->instance(ClaudeCriteriaExtractor::class, $claude);
        config(['services.anthropic.key' => 'test-key']);

        $this->actingAs(User::factory()->create())
            ->post('/ai', ['query' => 'Je cherche un développeur Flutter en Guinée'])
            ->assertRedirect();

        $search = AiSearch::firstOrFail();
        $this->assertSame('local', $search->criteria['engine']);
        $this->assertCount(1, $search->results);
    }

    public function test_claude_criteria_are_used_when_available(): void
    {
        $this->developer('Ibrahim Sow', 'GN', ['flutter' => 'expert']);

        $claude = Mockery::mock(ClaudeCriteriaExtractor::class);
        $claude->shouldReceive('extract')->once()->andReturn(new SearchCriteria(
            skills: ['flutter'], country: 'guinee', summary: 'Un développeur Flutter basé en Guinée.',
        ));
        $this->app->instance(ClaudeCriteriaExtractor::class, $claude);
        config(['services.anthropic.key' => 'test-key']);

        $this->actingAs(User::factory()->create())->post('/ai', ['query' => 'Il me faut quelqu\'un pour une app Flutter à Conakry']);

        $search = AiSearch::firstOrFail();
        $this->assertSame('claude', $search->criteria['engine']);
        $this->assertSame('Un développeur Flutter basé en Guinée.', $search->criteria['summary']);
        $this->assertSame('Ibrahim Sow', User::find($search->results[0]['user_id'])->name);
    }
}
