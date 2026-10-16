<?php

namespace Modules\Announcement\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Announcement\Filament\Resources\Widgets\WidgetResource;

class AnnouncementPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'announcement';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([WidgetResource::class]);
    }

    public function boot(Panel $panel): void {}
}
