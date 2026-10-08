<?php

namespace Modules\Office\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Office\Filament\Resources\Offices\OfficeResource;

class OfficePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'office';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([OfficeResource::class]);
    }

    public function boot(Panel $panel): void {}
}
