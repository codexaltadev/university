<?php

namespace Modules\Faq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Faq\Models\Faq;
use Platform\Relations\ContentRelation;

/** @extends Factory<Faq> */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => ['en' => $this->faker->sentence().'?'],
            'answer' => ['en' => '<p>'.$this->faker->paragraph().'</p>'],
        ];
    }

    /** Shown in these entities (test data: written directly, no permission checks). */
    public function shownIn(Model ...$targets): static
    {
        return $this->afterCreating(function (Faq $faq) use ($targets): void {
            foreach ($targets as $target) {
                ContentRelation::create([
                    'content_type' => $faq->getMorphClass(), 'content_id' => $faq->getKey(),
                    'target_type' => $target->getMorphClass(), 'target_id' => $target->getKey(),
                ]);
            }
        });
    }

    /** Published on the main site (site:0). */
    public function onMainSite(): static
    {
        return $this->afterCreating(fn (Faq $faq) => ContentRelation::create([
            'content_type' => $faq->getMorphClass(), 'content_id' => $faq->getKey(),
            'target_type' => 'site', 'target_id' => 0,
        ]));
    }
}
