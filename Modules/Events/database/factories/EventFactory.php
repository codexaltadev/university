<?php

namespace Modules\Events\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Events\Models\Event;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = now()->addDays($this->faker->numberBetween(2, 30))->setTime(10, 0);

        return [
            'title' => ['en' => rtrim($this->faker->unique()->sentence(5), '.')],
            'summary' => ['en' => $this->faker->sentence(12)],
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'venue' => ['en' => 'Auditorium, Daffodil Smart City'],
            'is_active' => true,
        ];
    }

    public function past(): static
    {
        return $this->state(fn () => ['starts_at' => now()->subDays(10)->setTime(10, 0), 'ends_at' => now()->subDays(10)->setTime(12, 0)]);
    }
}
