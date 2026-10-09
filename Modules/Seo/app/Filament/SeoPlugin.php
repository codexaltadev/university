<?php

namespace Modules\Seo\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Seo\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;

class SeoPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'seo';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([RedirectResource::class, NotFoundLogResource::class]);
    }

    public function boot(Panel $panel): void {}
}
