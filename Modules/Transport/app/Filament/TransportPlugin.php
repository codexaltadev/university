<?php

namespace Modules\Transport\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Transport\Filament\Resources\Routes\RouteResource;
use Modules\Transport\Filament\Resources\ServiceTypes\ServiceTypeResource;
use Modules\Transport\Filament\Resources\Stops\StopResource;

/** Transport › Routes, Stops, Service types (DB-06 v1.2 §2). */
class TransportPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'transport';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([RouteResource::class, StopResource::class, ServiceTypeResource::class]);
    }

    public function boot(Panel $panel): void {}
}
