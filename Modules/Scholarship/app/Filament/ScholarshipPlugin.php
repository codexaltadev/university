<?php

namespace Modules\Scholarship\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;

class ScholarshipPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'scholarship';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ScholarshipResource::class]);
    }

    public function boot(Panel $panel): void {}
}
