<?php

namespace Modules\Tuition\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Tuition\Filament\Clusters\TuitionFees;
use Modules\Tuition\Filament\Resources\FeeCategories\FeeCategoryResource;
use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;

class TuitionPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'tuition';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([TuitionFees::class]);
        $panel->resources([TuitionResource::class, FeeCategoryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
