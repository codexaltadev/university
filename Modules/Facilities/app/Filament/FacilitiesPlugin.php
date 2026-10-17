<?php

namespace Modules\Facilities\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Facilities\Filament\Resources\Facilities\FacilityResource;

class FacilitiesPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'facilities';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([FacilityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
