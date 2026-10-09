<?php

namespace Modules\Role\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Role\Filament\Resources\Roles\RoleResource;

class RolePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'role';
    }

    /** Registered before Shield's plugin, so Shield does not add its own role resource. */
    public function register(Panel $panel): void
    {
        $panel->resources([RoleResource::class]);
    }

    public function boot(Panel $panel): void {}
}
