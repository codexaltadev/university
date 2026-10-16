<?php

namespace Modules\News\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\News\Filament\Resources\Posts\PostResource;

class NewsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'news';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PostResource::class]);
    }

    public function boot(Panel $panel): void {}
}
