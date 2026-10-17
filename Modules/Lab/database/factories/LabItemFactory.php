<?php

namespace Modules\Lab\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Lab\Models\LabItem;

/** @extends Factory<LabItem> */
class LabItemFactory extends Factory
{
    protected $model = LabItem::class;

    public function definition(): array
    {
        return ['lab_id' => LabFactory::new(), 'item_type' => 'equipment', 'title' => ['en' => ucfirst($this->faker->words(3, true))], 'is_active' => true];
    }
}
