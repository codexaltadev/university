<?php

namespace Modules\Csr\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Csr\Models\Activity;

/** @extends Factory<Activity> */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return ['title' => ['en' => ucfirst($this->faker->words(4, true))], 'is_active' => true];
    }
}
