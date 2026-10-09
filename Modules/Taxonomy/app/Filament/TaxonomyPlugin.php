<?php

namespace Modules\Taxonomy\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Taxonomy\Filament\Resources\Vocabularies\VocabularyResource;

class TaxonomyPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'taxonomy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([VocabularyResource::class]);
    }

    public function boot(Panel $panel): void {}
}
