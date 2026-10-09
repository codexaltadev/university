<?php

namespace Modules\Fee\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Fee\Filament\Resources\FeeCategories\FeeCategoryResource;
use Modules\Fee\Filament\Resources\Tuitions\TuitionResource;

class FeePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'fee';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([TuitionResource::class, FeeCategoryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
