<?php

namespace Modules\Facilities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Facilities\Models\Facility;

/** @extends Factory<Facility> */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return ['title' => ['en' => ucfirst($this->faker->words(2, true))], 'is_active' => true];
    }
}
