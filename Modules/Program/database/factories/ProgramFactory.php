<?php

namespace Modules\Program\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Models\Program;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $name = 'BSc in '.Str::title($this->faker->unique()->words(2, true));

        return [
            'department_id' => Department::factory(),
            'level_term_id' => fn (): int => static::level('Undergraduate'),
            'name' => ['en' => $name],
            'short_name' => Str::upper(Str::substr(Str::replace(' ', '', Str::after($name, 'BSc in ')), 0, 6)).$this->faker->unique()->numberBetween(1, 999),
            'summary' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
            'total_credits' => 144,
            'duration_years' => 4,
            'total_terms' => 12,
            'shift' => 'day',
            'class_mode' => 'on_campus',
            'published_at' => now()->subDay(),
        ];
    }

    /** Id of a default level ("Undergraduate", "Graduate" …), seeding the levels when needed. */
    public static function level(string $name): int
    {
        Vocabularies::ensureTerms('program.level', ProgramDatabaseSeeder::LEVELS);
        $vocabulary = app(Vocabularies::class)->find('program.level');

        return Term::query()->where('vocabulary_id', $vocabulary->id)->get()
            ->first(fn (Term $t): bool => $t->getTranslation('name', 'en') === $name)->id;
    }
}
