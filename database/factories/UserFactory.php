<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const FIRST_NAMES = [
        'Alpha', 'Mamadou', 'Ibrahim', 'Aminata', 'Fatoumata', 'Moussa', 'Awa', 'Ousmane', 'Mariama', 'Kofi',
        'Ama', 'Chinedu', 'Ngozi', 'Amadou', 'Aïssatou', 'Kwame', 'Thandiwe', 'Youssef', 'Salma', 'Omar',
        'Nadia', 'Jean-Paul', 'Grace', 'Emmanuel', 'Aïcha', 'Seydou', 'Binta', 'Tidiane', 'Koffi', 'Esther',
    ];

    public const LAST_NAMES = [
        'Diallo', 'Barry', 'Bah', 'Camara', 'Keita', 'Touré', 'Sow', 'Traoré', 'Diop', 'Ndiaye',
        'Mensah', 'Okafor', 'Kamara', 'Koné', 'Coulibaly', 'Cissé', 'Sylla', 'Nkosi', 'Benali', 'Owusu',
        'Adeyemi', 'Mutua', 'Kariuki', 'Haidara', 'Fofana', 'Konaté', 'Sangaré', 'Bamba', 'Kouassi', 'Mbaye',
    ];

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->randomElement(self::FIRST_NAMES);
        $lastName = fake()->randomElement(self::LAST_NAMES);

        return [
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'bio' => fake()->sentence(15),
            'profile_type' => fake()->randomElement(ProfileType::cases()),
            'experience_level' => fake()->randomElement(ExperienceLevel::cases()),
            'availability' => fake()->randomElement(Availability::cases()),
            'github_url' => 'https://github.com/'.Str::slug($firstName.$lastName).fake()->numberBetween(1, 99),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
        ]);
    }
}
