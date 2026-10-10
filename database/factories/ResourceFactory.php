<?php

namespace Database\Factories;

use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(3, true));

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'description' => fake()->paragraph(3),
            'type' => fake()->randomElement(ResourceType::cases()),
            'status' => ResourceStatus::Approved,
            'github_url' => 'https://github.com/devlink-africa/'.Str::slug($title),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResourceStatus::Pending,
        ]);
    }
}
