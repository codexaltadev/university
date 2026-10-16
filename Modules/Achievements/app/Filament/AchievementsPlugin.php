<?php

namespace Modules\Achievements\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Achievements\Filament\Resources\Achievements\AchievementResource;

class AchievementsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'achievements';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AchievementResource::class]);
    }

    public function boot(Panel $panel): void {}
}
