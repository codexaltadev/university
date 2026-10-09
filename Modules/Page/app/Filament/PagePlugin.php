<?php

namespace Modules\Page\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Page\Filament\Resources\Pages\PageResource;

class PagePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'page';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PageResource::class]);
    }

    public function boot(Panel $panel): void {}
}
