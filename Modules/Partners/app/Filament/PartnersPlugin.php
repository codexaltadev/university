<?php

namespace Modules\Partners\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Partners\Filament\Resources\Partners\PartnerResource;

class PartnersPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'partners';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PartnerResource::class]);
    }

    public function boot(Panel $panel): void {}
}
