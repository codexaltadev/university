<?php

namespace Modules\Statistics\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Statistics\Filament\Pages\ManageStatistics;

class StatisticsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'statistics';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageStatistics::class]);
    }

    public function boot(Panel $panel): void {}
}
