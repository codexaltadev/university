<?php

namespace Modules\Accreditation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accreditation\Models\Accreditation;

/** @extends Factory<Accreditation> */
class AccreditationFactory extends Factory
{
    protected $model = Accreditation::class;

    public function definition(): array
    {
        return ['title' => ['en' => ucfirst($this->faker->words(3, true))], 'agency' => ['en' => $this->faker->company()], 'is_active' => true];
    }
}
