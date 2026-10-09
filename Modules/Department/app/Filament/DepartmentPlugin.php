<?php

namespace Modules\Department\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;

class DepartmentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'department';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([DepartmentResource::class]);
    }

    public function boot(Panel $panel): void {}
}
