<?php

namespace Modules\Gallery\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Gallery\Filament\Resources\Albums\AlbumResource;

class GalleryPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'gallery';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AlbumResource::class]);
    }

    public function boot(Panel $panel): void {}
}
