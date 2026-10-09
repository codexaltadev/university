<?php

namespace Modules\Menu\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Menu\Filament\Pages\ManageMenus;

class MenuPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'menu';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageMenus::class]);
    }

    public function boot(Panel $panel): void {}
}
