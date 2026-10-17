<?php

namespace Modules\Lab\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Lab\Filament\Resources\Labs\LabResource;

class LabPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'lab';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([LabResource::class]);
    }

    public function boot(Panel $panel): void {}
}
