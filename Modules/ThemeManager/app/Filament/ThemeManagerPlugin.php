<?php

namespace Modules\ThemeManager\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\ThemeManager\Filament\Pages\ManageDesign;
use Modules\ThemeManager\Filament\Pages\ManageLayouts;
use Modules\ThemeManager\Filament\Pages\ManageThemes;

class ThemeManagerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'theme-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageThemes::class, ManageDesign::class, ManageLayouts::class]);
    }

    public function boot(Panel $panel): void {}
}
