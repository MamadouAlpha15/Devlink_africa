<?php

namespace Database\Factories;

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->randomElement(array_keys(CountrySeeder::COUNTRIES));
        $name = CountrySeeder::COUNTRIES[$code];

        return [
            'code' => $code,
            'name' => $name,
            'flag' => CountrySeeder::flag($code),
            'slug' => Str::slug($name),
        ];
    }
}
