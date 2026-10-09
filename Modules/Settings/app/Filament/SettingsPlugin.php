<?php

namespace Modules\Settings\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Modules\Settings\Filament\Pages\ManageSettings;
use Modules\Settings\Groups\AdminBrandingSettings;

class SettingsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'settings';
    }

    /** Admin Panel Branding (FR-SET-13): read from settings on every request, cached. */
    public function register(Panel $panel): void
    {
        $panel
            ->pages([ManageSettings::class])
            ->brandName(fn (): string => settings('admin_branding.panel_name') ?: AdminBrandingSettings::NEUTRAL_NAME)
            ->colors(fn (): array => ['primary' => Color::hex(settings('admin_branding.primary_color') ?: AdminBrandingSettings::NEUTRAL_COLOR)])
            ->font(fn (): string => settings('admin_branding.font') ?: 'Inter')
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, function (): ?HtmlString {
                $message = settings('admin_branding.login_message');

                return filled($message)
                    ? new HtmlString('<p class="text-center text-sm text-gray-600 dark:text-gray-300">'.e($message).'</p>')
                    : null;
            });
    }

    public function boot(Panel $panel): void
    {
        // Admin dates in the university's timezone; stored values stay in app.timezone
        FilamentTimezone::set(settings('general.timezone') ?: config('app.timezone'));
    }
}
