<?php

namespace Modules\Notice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notice\Models\Notice;

/** @extends Factory<Notice> */
class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => rtrim($this->faker->unique()->sentence(6), '.')],
            'body' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
            'is_active' => true,
        ];
    }
}
