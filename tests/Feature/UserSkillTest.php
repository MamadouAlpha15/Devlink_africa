<?php

namespace Tests\Feature;

use App\Enums\SkillLevel;
use App\Http\Requests\StoreUserSkillRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSkillTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_skill_can_be_added_with_a_level(): void
    {
        $user = User::factory()->create();
        $skill = Skill::factory()->create();

        $this->actingAs($user)
            ->post('/profile/skills', ['skill_id' => $skill->id, 'level' => 'advanced'])
            ->assertSessionHasNoErrors();

        $this->assertSame(SkillLevel::Advanced, $user->skills()->first()->pivot->level);
    }

    public function test_adding_an_existing_skill_updates_its_level_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $skill = Skill::factory()->create();

        $this->actingAs($user)->post('/profile/skills', ['skill_id' => $skill->id, 'level' => 'beginner']);
        $this->actingAs($user)->post('/profile/skills', ['skill_id' => $skill->id, 'level' => 'expert']);

        $this->assertSame(1, $user->skills()->count());
        $this->assertSame(SkillLevel::Expert, $user->skills()->first()->pivot->level);
    }

    public function test_the_level_can_be_changed_and_the_skill_removed(): void
    {
        $user = User::factory()->create();
        $skill = Skill::factory()->create();
        $user->skills()->attach($skill->id, ['level' => 'beginner']);

        $this->actingAs($user)->patch("/profile/skills/{$skill->slug}", ['level' => 'intermediate']);
        $this->assertSame(SkillLevel::Intermediate, $user->skills()->first()->pivot->level);

        $this->actingAs($user)->delete("/profile/skills/{$skill->slug}");
        $this->assertSame(0, $user->skills()->count());
    }

    public function test_invalid_skill_or_level_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/skills', ['skill_id' => 9999, 'level' => 'god'])
            ->assertSessionHasErrors(['skill_id', 'level']);
    }

    public function test_the_number_of_skills_is_limited(): void
    {
        $user = User::factory()->create();
        $user->skills()->attach(
            Skill::factory(StoreUserSkillRequest::MAX_SKILLS)->create()->mapWithKeys(fn (Skill $skill) => [$skill->id => ['level' => 'beginner']])
        );

        $this->actingAs($user)
            ->post('/profile/skills', ['skill_id' => Skill::factory()->create()->id, 'level' => 'expert'])
            ->assertSessionHasErrors('skill_id');
    }
}
