<?php

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\MailingList;

/** @extends Factory<MailingList> */
class MailingListFactory extends Factory
{
    protected $model = MailingList::class;

    public function definition(): array
    {
        return ['key' => $this->faker->unique()->slug(2), 'name' => ['en' => ucfirst($this->faker->words(2, true))], 'is_active' => true];
    }
}
