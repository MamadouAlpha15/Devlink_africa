<?php

namespace Database\Seeders;

use App\Enums\SkillCategory;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    /**
     * Skills offered on the platform, grouped by category.
     */
    public const SKILLS = [
        'backend' => ['PHP', 'Laravel', 'Symfony', 'Node.js', 'Express', 'Python', 'Django', 'Java', 'Spring Boot', 'C#', '.NET', 'Go'],
        'frontend' => ['JavaScript', 'TypeScript', 'React', 'Vue', 'Angular', 'Next.js', 'HTML/CSS', 'Tailwind CSS', 'Bootstrap'],
        'mobile' => ['Flutter', 'Dart', 'React Native', 'Kotlin', 'Swift', 'Android', 'iOS'],
        'design' => ['UI/UX', 'Figma', 'Design graphique'],
        'devops' => ['DevOps', 'Docker', 'Linux', 'AWS', 'CI/CD'],
        'data' => ['Data Science', 'Machine Learning', 'Intelligence artificielle'],
        'database' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Firebase'],
        'other' => ['Cybersécurité', 'Blockchain', 'Gestion de projet'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [];

        foreach (self::SKILLS as $category => $names) {
            foreach ($names as $name) {
                $rows[] = [
                    'name' => $name,
                    'slug' => self::slug($name),
                    'category' => SkillCategory::from($category)->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Skill::upsert($rows, ['slug'], ['name', 'category', 'updated_at']);
    }

    /**
     * "Node.js" → "node-js", "C#" → "csharp", "UI/UX" → "ui-ux".
     */
    public static function slug(string $name): string
    {
        return Str::slug(str_replace(['#', '.', '/'], ['sharp', ' ', ' '], $name));
    }
}
