<?php

namespace Modules\ModuleManager\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleResource;

class ModuleManagerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'module-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ModuleResource::class]);
    }

    public function boot(Panel $panel): void {}
}
