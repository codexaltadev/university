<?php

namespace Modules\Seo\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Seo\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;
use Modules\Seo\Filament\Resources\SearchTerms\SearchTermResource;

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
        $panel->resources([RedirectResource::class, NotFoundLogResource::class, SearchTermResource::class]);
    }

    public function boot(Panel $panel): void {}
}
