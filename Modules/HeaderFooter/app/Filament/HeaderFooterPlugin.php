<?php

namespace Modules\HeaderFooter\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\HeaderFooter\Filament\Pages\ManageHeaderFooter;

class HeaderFooterPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'header-footer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageHeaderFooter::class]);
    }

    public function boot(Panel $panel): void {}
}
