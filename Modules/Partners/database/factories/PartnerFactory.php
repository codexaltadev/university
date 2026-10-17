<?php

namespace Modules\Partners\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Partners\Models\Partner;

/** @extends Factory<Partner> */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return ['name' => ['en' => $this->faker->company()], 'partner_type' => 'industry', 'is_active' => true];
    }
}
