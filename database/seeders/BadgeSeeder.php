<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            ['slug' => 'premier-projet', 'name' => 'Premier projet', 'icon' => '🚀', 'criteria' => 'first_project',
                'description' => 'A créé son premier projet collaboratif.'],
            ['slug' => 'expert-laravel', 'name' => 'Expert Laravel', 'icon' => '🔥', 'criteria' => 'laravel_expert',
                'description' => 'Niveau expert en Laravel.'],
            ['slug' => 'collaborateur', 'name' => 'Collaborateur', 'icon' => '🤝', 'criteria' => 'collaborator',
                'description' => 'A rejoint un projet d\'un autre membre.'],
            ['slug' => 'contributeur-actif', 'name' => 'Contributeur actif', 'icon' => '⭐', 'criteria' => 'active_contributor',
                'description' => 'A publié au moins 3 ressources.'],
            ['slug' => 'panafricain', 'name' => 'Panafricain', 'icon' => '🌍', 'criteria' => 'panafrican',
                'description' => 'A collaboré avec des membres d\'au moins 3 pays.'],
            ['slug' => 'builder', 'name' => 'Builder', 'icon' => '🏗️', 'criteria' => 'builder',
                'description' => 'A mené un projet jusqu\'au bout.'],
        ];

        $rows = array_map(fn (array $badge) => $badge + ['created_at' => now(), 'updated_at' => now()], $badges);

        Badge::upsert($rows, ['slug'], ['name', 'description', 'icon', 'criteria', 'updated_at']);
    }
}
