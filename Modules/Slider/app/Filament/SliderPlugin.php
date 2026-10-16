<?php

namespace Modules\Slider\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Slider\Filament\Resources\Slides\SlideResource;

class SliderPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'slider';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([SlideResource::class]);
    }

    public function boot(Panel $panel): void {}
}
