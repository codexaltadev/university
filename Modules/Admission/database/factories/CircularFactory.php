<?php

namespace Modules\Admission\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admission\Models\Circular;

/** @extends Factory<Circular> */
class CircularFactory extends Factory
{
    protected $model = Circular::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => 'Admission '.$this->faker->unique()->numerify('Fall 20##')],
            'session_label' => 'Fall 2026',
            'content' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
            'apply_starts_on' => today()->subWeek(),
            'apply_ends_on' => today()->addMonth(),
            'test_on' => today()->addMonth()->addDays(3),
            'is_active' => true,
        ];
    }

    /** Applications closed. */
    public function closed(): static
    {
        return $this->state(['apply_starts_on' => today()->subMonths(3), 'apply_ends_on' => today()->subMonth(), 'test_on' => today()->subWeeks(3)]);
    }
}
