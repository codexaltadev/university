<?php

namespace Modules\Announcement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Announcement\Models\Widget;

/** @extends Factory<Widget> */
class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    public function definition(): array
    {
        return [
            'widget_type' => 'bar',
            'title' => ['en' => ucfirst($this->faker->words(4, true))],
            'is_active' => true,
        ];
    }
}
