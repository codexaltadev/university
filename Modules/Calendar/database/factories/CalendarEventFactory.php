<?php

namespace Modules\Calendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'calendar_id' => Calendar::factory(),
            'title' => ['en' => $this->faker->randomElement(['Classes Start', 'Mid-Examination', 'Final Examination', 'Semester Break'])],
            'starts_on' => now()->addDays($this->faker->numberBetween(1, 60))->toDateString(),
            'is_active' => true,
        ];
    }

    /** Only words, no exact date. */
    public function undated(string $words = '1st week of March'): static
    {
        return $this->state(['starts_on' => null, 'ends_on' => null, 'date_label' => ['en' => $words]]);
    }
}
