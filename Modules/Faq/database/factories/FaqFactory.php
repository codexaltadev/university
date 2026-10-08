<?php

namespace Modules\Faq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Faq\Models\Faq;

/** @extends Factory<Faq> */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence().'?',
            'answer' => $this->faker->paragraph(),
            'department_id' => null,
        ];
    }

    public function forDepartment(int $id): static
    {
        return $this->state(['department_id' => $id]);
    }
}
