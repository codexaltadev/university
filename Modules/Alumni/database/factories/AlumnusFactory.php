<?php

namespace Modules\Alumni\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Alumni\Models\Alumnus;

/** @extends Factory<Alumnus> */
class AlumnusFactory extends Factory
{
    protected $model = Alumnus::class;

    public function definition(): array
    {
        return [
            'name' => ['en' => $this->faker->name()],
            'batch' => (string) $this->faker->numberBetween(10, 50),
            'passing_year' => $this->faker->numberBetween(2008, 2024),
            'current_designation' => ['en' => $this->faker->jobTitle()],
            'organization' => ['en' => $this->faker->company()],
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function pride(): static
    {
        return $this->state(['is_pride' => true]);
    }
}
