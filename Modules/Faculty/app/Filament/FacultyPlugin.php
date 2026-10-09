<?php

namespace Modules\Faculty\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;

class FacultyPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'faculty';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([FacultyResource::class]);
    }

    public function boot(Panel $panel): void {}
}
