<?php

namespace Modules\Accreditation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Accreditation\Filament\Resources\Accreditations\AccreditationResource;
use Modules\Accreditation\Filament\Resources\Rankings\RankingResource;

class AccreditationPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'accreditation';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AccreditationResource::class, RankingResource::class]);
    }

    public function boot(Panel $panel): void {}
}
