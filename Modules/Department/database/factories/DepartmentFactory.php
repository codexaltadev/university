<?php

namespace Modules\Department\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;

/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = 'Department of '.Str::title($this->faker->unique()->words(2, true));

        return [
            'faculty_id' => Faculty::factory(),
            'name' => ['en' => $name],
            'short_name' => Str::upper(Str::substr(Str::replace(' ', '', Str::after($name, 'Department of ')), 0, 5)).$this->faker->unique()->numberBetween(1, 999),
            'about' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
        ];
    }
}
