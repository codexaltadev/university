<?php

namespace Modules\Committee\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Committee\Filament\Resources\Committees\CommitteeResource;

class CommitteePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'committee';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CommitteeResource::class]);
    }

    public function boot(Panel $panel): void {}
}
