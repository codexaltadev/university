<?php

namespace Modules\News\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\News\Models\Post;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => rtrim($this->faker->unique()->sentence(6), '.')],
            'excerpt' => ['en' => $this->faker->sentence(14)],
            'body' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
            'is_active' => true,
        ];
    }

    /** Shows from a future time (AC-NEWS-02). */
    public function scheduled(string $at = '+1 day'): static
    {
        return $this->state(['active_at' => now()->modify($at)]);
    }
}
