<?php

namespace Modules\Achievements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achievements\Models\Achievement;

/** @extends Factory<Achievement> */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => ucfirst($this->faker->words(5, true))],
            'achieved_on' => $this->faker->dateTimeBetween('-2 years')->format('Y-m-d'),
            'level' => 'national',
            'is_active' => true,
        ];
    }
}
