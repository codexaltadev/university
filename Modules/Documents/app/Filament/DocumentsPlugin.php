<?php

namespace Modules\Documents\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Documents\Filament\Resources\Documents\DocumentResource;

class DocumentsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'documents';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([DocumentResource::class]);
    }

    public function boot(Panel $panel): void {}
}
