<?php

namespace Modules\Scholarship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Scholarship\Models\Scholarship;

/** @extends Factory<Scholarship> */
class ScholarshipFactory extends Factory
{
    protected $model = Scholarship::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->unique()->words(3, true).' Scholarship'],
            'summary' => ['en' => $this->faker->sentence()],
            'details' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
            'is_active' => true,
        ];
    }

    /** A call or opportunity under a scholarship. */
    public function partOf(Scholarship $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
