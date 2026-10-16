<?php

namespace Modules\Slider\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Slider\Models\Slide;

/** @extends Factory<Slide> */
class SlideFactory extends Factory
{
    protected $model = Slide::class;

    public function definition(): array
    {
        return [
            'placement' => 'hero',
            'title' => ['en' => ucfirst($this->faker->words(4, true))],
            'is_active' => true,
        ];
    }

    public function banner(): static
    {
        return $this->state(['placement' => 'page_banner']);
    }
}
