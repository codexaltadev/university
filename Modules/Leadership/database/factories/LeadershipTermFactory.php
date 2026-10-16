<?php

namespace Modules\Leadership\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leadership\Models\LeadershipTerm;

/**
 * Terms are made with Appointments::appoint() (the person lives on the core assignment);
 * this factory only exists for HasFactory.
 *
 * @extends Factory<LeadershipTerm>
 */
class LeadershipTermFactory extends Factory
{
    protected $model = LeadershipTerm::class;

    public function definition(): array
    {
        return ['message' => ['en' => '<p>'.$this->faker->paragraph().'</p>'], 'is_active' => true];
    }
}
