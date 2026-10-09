<?php

namespace Modules\Media\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Modules\Media\Filament\Resources\MediaItems\MediaItemResource;

class MediaPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'media';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([MediaItemResource::class])
            // Grid card styles; Filament's compiled CSS has only its own classes
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('media::grid-styles'));
    }

    public function boot(Panel $panel): void {}
}
