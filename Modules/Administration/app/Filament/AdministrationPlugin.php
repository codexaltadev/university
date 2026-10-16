<?php

namespace Modules\Administration\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Administration\Filament\Resources\Categories\AdministrationCategoryResource;
use Modules\Administration\Filament\Resources\Members\MemberResource;

class AdministrationPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'administration';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MemberResource::class, AdministrationCategoryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
