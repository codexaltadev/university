<?php

namespace Modules\International\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\International\Models\Institution;

/** @extends Factory<Institution> */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return ['name' => ['en' => $this->faker->company().' University'], 'country_id' => CountryFactory::new(), 'is_active' => true];
    }
}
