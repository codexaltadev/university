<?php

namespace Modules\Csr\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Csr\Filament\Pages\CsrCategories;
use Modules\Csr\Filament\Resources\Activities\ActivityResource;

class CsrPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'csr';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ActivityResource::class])->pages([CsrCategories::class]);
    }

    public function boot(Panel $panel): void {}
}
