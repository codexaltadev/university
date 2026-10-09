<?php

namespace Modules\Homepage\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Homepage\Filament\Pages\ManageHomepage;

class HomepagePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'homepage';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageHomepage::class]);
    }

    public function boot(Panel $panel): void {}
}
