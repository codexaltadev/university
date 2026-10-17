<?php

namespace Modules\International\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\International\Models\Conference;

/** @extends Factory<Conference> */
class ConferenceFactory extends Factory
{
    protected $model = Conference::class;

    public function definition(): array
    {
        return ['title' => ['en' => ucfirst($this->faker->words(4, true))], 'starts_on' => now()->addMonth()->toDateString(), 'is_active' => true];
    }
}
