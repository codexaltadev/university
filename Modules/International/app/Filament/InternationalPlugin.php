<?php

namespace Modules\International\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\International\Filament\Resources\Conferences\ConferenceResource;
use Modules\International\Filament\Resources\Countries\CountryResource;
use Modules\International\Filament\Resources\Institutions\InstitutionResource;
use Modules\International\Filament\Resources\Visitors\VisitorResource;

class InternationalPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'international';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([InstitutionResource::class, ConferenceResource::class, VisitorResource::class, CountryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
