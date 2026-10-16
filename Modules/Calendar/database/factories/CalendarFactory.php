<?php

namespace Modules\Calendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\Models\Calendar;

/** @extends Factory<Calendar> */
class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    public function definition(): array
    {
        $year = (int) date('Y');
        $season = $this->faker->randomElement(['Spring', 'Summer', 'Fall']).' '.$year;

        return [
            'title' => ['en' => $season.' '.$this->faker->unique()->numerify('(###)')],
            'session_label' => $season,
            'year' => $year,
            'duration_label' => ['en' => 'January – April'],
            'show_on_home' => true,
            'is_active' => true,
        ];
    }
}
