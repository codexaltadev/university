<?php

namespace Modules\Faculty\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Faculty\Models\Faculty;

/** @extends Factory<Faculty> */
class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        $name = 'Faculty of '.Str::title($this->faker->unique()->words(2, true));

        return [
            'name' => ['en' => $name],
            'short_name' => Str::upper(Str::substr(Str::replace(' ', '', Str::after($name, 'Faculty of ')), 0, 5)).$this->faker->unique()->numberBetween(1, 999),
            'overview' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
        ];
    }
}
