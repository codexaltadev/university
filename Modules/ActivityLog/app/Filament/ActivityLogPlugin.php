<?php

namespace Modules\ActivityLog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\ActivityLog\Filament\Resources\Activities\ActivityResource;

class ActivityLogPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'activity-log';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ActivityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
