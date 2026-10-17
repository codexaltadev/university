<?php

namespace Modules\Lab\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Lab\Models\Lab;

/** @extends Factory<Lab> */
class LabFactory extends Factory
{
    protected $model = Lab::class;

    public function definition(): array
    {
        return ['name' => ['en' => ucfirst($this->faker->words(2, true)).' Lab'], 'is_active' => true];
    }
}
