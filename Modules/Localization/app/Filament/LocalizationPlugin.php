<?php

namespace Modules\Localization\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Localization\Filament\Pages\InterfaceTexts;
use Modules\Localization\Filament\Resources\Languages\LanguageResource;
use Modules\Localization\Http\Middleware\SetAdminLocale;

class LocalizationPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'localization';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([LanguageResource::class])
            ->pages([InterfaceTexts::class])
            ->middleware([SetAdminLocale::class], isPersistent: true);
    }

    public function boot(Panel $panel): void {}
}
