<?php

namespace Modules\{{Name}}\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\{{Name}}\Filament\Resources\{{Name}}Items\{{ResourceName}};

class {{Name}}Plugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return '{{slug}}';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([{{ResourceName}}::class]);
    }

    public function boot(Panel $panel): void {}
}
