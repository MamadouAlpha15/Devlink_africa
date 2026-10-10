<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function titles(TestResponse $response): array
    {
        return $response->viewData('projects')->pluck('title')->all();
    }

    public function test_projects_can_be_filtered(): void
    {
        $guinea = Country::factory()->create(['slug' => 'guinee']);
        $flutter = Skill::factory()->create(['slug' => 'flutter']);

        $delivery = Project::factory()->create(['title' => 'AfricaDelivery', 'category' => 'logistics', 'country_id' => $guinea->id, 'description' => 'Livraison rapide.']);
        $delivery->skills()->attach($flutter->id);
        Project::factory()->create(['title' => 'FinTech Africa', 'category' => 'fintech', 'status' => 'in_progress', 'description' => 'Mobile money.']);
        $health = Project::factory()->create(['title' => 'HealthTech Guinea', 'category' => 'healthtech', 'description' => 'Santé.']);
        $health->roles()->create(['title' => 'Flutter Developer', 'skill_id' => $flutter->id, 'slots' => 1]);

        $this->assertSame(['AfricaDelivery'], $this->titles($this->get('/projects?q=livraison')));
        $this->assertSame(['FinTech Africa'], $this->titles($this->get('/projects?category=fintech')));
        $this->assertSame(['AfricaDelivery'], $this->titles($this->get('/projects?country=guinee')));
        $this->assertSame(['FinTech Africa'], $this->titles($this->get('/projects?status=in_progress')));
        // A skill matches the project technologies or the roles it is recruiting.
        $this->assertEqualsCanonicalizing(['AfricaDelivery', 'HealthTech Guinea'], $this->titles($this->get('/projects?skill=flutter')));
    }

    public function test_archived_projects_are_hidden_unless_requested(): void
    {
        Project::factory()->create(['title' => 'Active']);
        Project::factory()->create(['title' => 'Old', 'status' => 'archived']);

        $this->assertSame(['Active'], $this->titles($this->get('/projects')));
        $this->assertSame(['Old'], $this->titles($this->get('/projects?status=archived')));
    }

    public function test_invalid_filters_are_ignored(): void
    {
        Project::factory()->create(['title' => 'AfricaDelivery']);

        $this->assertSame(['AfricaDelivery'], $this->titles($this->get('/projects?category=x&status[]=y&sort=z')->assertOk()));
    }
}
