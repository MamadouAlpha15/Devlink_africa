<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reference data (safe to run several times).
        $this->call([
            CountrySeeder::class,
            SkillSeeder::class,
            BadgeSeeder::class,
        ]);

        // Demo community: run on a fresh database (php artisan migrate:fresh --seed).
        $this->call(DemoSeeder::class);
    }
}
