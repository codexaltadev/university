<?php

namespace Modules\International\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\International\Models\Visitor;

/** @extends Factory<Visitor> */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    public function definition(): array
    {
        return ['name' => ['en' => $this->faker->name()], 'visited_on' => now()->subWeek()->toDateString(), 'is_active' => true];
    }
}
