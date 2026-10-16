<?php

namespace Modules\Events\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Events\Filament\Resources\Events\EventResource;

class EventsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'events';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([EventResource::class]);
    }

    public function boot(Panel $panel): void {}
}
