<?php

namespace Database\Factories;

use App\Enums\ProjectCategory;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => ucfirst(fake()->words(2, true)).' Africa',
            'description' => fake()->paragraph(4),
            'category' => fake()->randomElement(ProjectCategory::cases()),
            'status' => ProjectStatus::Recruiting,
            'members_wanted' => fake()->numberBetween(1, 5),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
