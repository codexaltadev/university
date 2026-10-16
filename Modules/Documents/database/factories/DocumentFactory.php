<?php

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Models\Document;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => ucfirst($this->faker->words(4, true))],
            'document_type' => 'other',
            'is_active' => true,
        ];
    }
}
