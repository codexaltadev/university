<?php

namespace Modules\International\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\International\Models\Country;

/** @extends Factory<Country> */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return ['name' => ['en' => $this->faker->unique()->country()], 'iso2' => strtoupper($this->faker->unique()->lexify('??')), 'is_active' => true];
    }
}
