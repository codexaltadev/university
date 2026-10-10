<?php

namespace Modules\Program\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Program\Filament\Resources\Programs\ProgramResource;

class ProgramPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'program';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ProgramResource::class]);
    }

    public function boot(Panel $panel): void {}
}
