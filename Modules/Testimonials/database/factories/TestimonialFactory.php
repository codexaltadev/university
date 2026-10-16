<?php

namespace Modules\Testimonials\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Testimonials\Models\Testimonial;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'quote' => ['en' => $this->faker->sentence(18)],
            'testimonial_type' => 'student',
            'is_active' => true,
        ];
    }
}
