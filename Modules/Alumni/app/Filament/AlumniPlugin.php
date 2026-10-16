<?php

namespace Modules\Alumni\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Alumni\Filament\Resources\Alumni\AlumnusResource;

class AlumniPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'alumni';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AlumnusResource::class]);
    }

    public function boot(Panel $panel): void {}
}
