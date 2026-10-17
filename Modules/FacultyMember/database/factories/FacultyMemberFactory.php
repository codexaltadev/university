<?php

namespace Modules\FacultyMember\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FacultyMember\Models\FacultyMember;

/** @extends Factory<FacultyMember> */
class FacultyMemberFactory extends Factory
{
    protected $model = FacultyMember::class;

    public function definition(): array
    {
        return [
            'external_ref' => 'T-'.$this->faker->unique()->numberBetween(1000, 999999),
            'name' => ['en' => $this->faker->name()],
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+8801'.$this->faker->numerify('#########'),
            'employment_type' => 'full_time',
            'is_active' => true,
        ];
    }
}
