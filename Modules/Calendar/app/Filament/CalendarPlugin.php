<?php

namespace Modules\Calendar\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Calendar\Filament\Resources\Calendars\CalendarResource;

class CalendarPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'calendar';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CalendarResource::class]);
    }

    public function boot(Panel $panel): void {}
}
