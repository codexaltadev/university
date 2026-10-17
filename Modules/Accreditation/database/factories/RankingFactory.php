<?php

namespace Modules\Accreditation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accreditation\Models\Ranking;

/** @extends Factory<Ranking> */
class RankingFactory extends Factory
{
    protected $model = Ranking::class;

    public function definition(): array
    {
        return ['agency_name' => ['en' => 'QS World University Rankings'], 'agency_key' => 'qs', 'year' => (int) now()->year, 'rank_label' => (string) $this->faker->numberBetween(100, 1500), 'is_active' => true];
    }
}
