<?php

namespace Modules\Committee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Committee\Models\Committee;

/** @extends Factory<Committee> */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    public function definition(): array
    {
        return [
            'name' => ['en' => ucfirst($this->faker->unique()->words(2, true)).' Committee'],
            'description' => ['en' => '<p>'.$this->faker->sentence().'</p>'],
            'is_active' => true,
        ];
    }
}
