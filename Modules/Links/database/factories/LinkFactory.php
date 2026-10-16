<?php

namespace Modules\Links\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Links\Models\Link;

/** @extends Factory<Link> */
class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => ucfirst($this->faker->words(2, true))],
            'url' => 'https://'.$this->faker->domainName().'/',
            'is_active' => true,
        ];
    }
}
