<?php

namespace Modules\Waiver\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Waiver\Filament\Clusters\WaiverCalculator;
use Modules\Waiver\Filament\Resources\Boards\BoardResource;
use Modules\Waiver\Filament\Resources\WaiverCategories\WaiverCategoryResource;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;

class WaiverPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'waiver';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([WaiverCalculator::class]);
        $panel->resources([WaiverRuleResource::class, WaiverCategoryResource::class, BoardResource::class]);
    }

    public function boot(Panel $panel): void {}
}
