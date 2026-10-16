<?php

namespace Modules\Gallery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Gallery\Models\Album;

/** @extends Factory<Album> */
class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => ucfirst($this->faker->words(3, true))],
            'is_active' => true,
        ];
    }
}
