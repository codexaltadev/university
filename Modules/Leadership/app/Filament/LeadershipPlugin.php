<?php

namespace Modules\Leadership\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Leadership\Filament\Resources\Positions\PositionResource;

class LeadershipPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'leadership';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PositionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
