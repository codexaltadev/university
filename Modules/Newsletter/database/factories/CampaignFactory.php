<?php

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\Campaign;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return ['subject' => ucfirst($this->faker->words(5, true)), 'body' => '<p>'.$this->faker->sentence().'</p>', 'locale' => 'en', 'is_active' => true];
    }
}
