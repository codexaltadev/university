<?php

namespace Modules\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Page\Models\Page;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->unique()->words(3, true)],
            'content' => ['en' => [['type' => 'text', 'data' => ['body' => '<p>'.$this->faker->paragraph().'</p>']]]],
            'published_at' => now()->subDay(),
        ];
    }

    /** Owned by an entity instead of the main site. */
    public function ownedBy(Model $entity): static
    {
        return $this->state(fn (): array => ['owner_type' => $entity->getMorphClass(), 'owner_id' => $entity->getKey()]);
    }
}
