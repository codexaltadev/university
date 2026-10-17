<?php

namespace Modules\Transport\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Transport\Models\Route;

/** @extends Factory<Route> */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name' => ['en' => $this->faker->city().' <> DSC'],
            'route_group' => 'regular',
            'is_active' => true,
        ];
    }
}
