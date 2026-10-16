<?php

namespace Modules\Links\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Links\Filament\Resources\Links\LinkResource;

class LinksPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'links';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([LinkResource::class]);
    }

    public function boot(Panel $panel): void {}
}
