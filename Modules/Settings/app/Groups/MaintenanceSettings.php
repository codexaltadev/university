<?php

namespace Modules\Settings\Groups;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Modules\Settings\Support\SettingsAccess;
use Platform\Filament\Forms\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/** FR-SET-08: visitors see a message; signed-in admins still see the website. */
class MaintenanceSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'maintenance';
    }

    public function label(): string
    {
        return __('settings::settings.groups.maintenance');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedWrenchScrewdriver;
    }

    public function sort(): int
    {
        return 70;
    }

    public function translatable(): array
    {
        return ['message'];
    }

    public function defaults(): array
    {
        return ['enabled' => false];
    }

    public function canAccess(User $user): bool
    {
        return SettingsAccess::advanced($user);
    }

    public function schema(): array
    {
        return [
            Toggle::make('enabled')->label(__('settings::settings.fields.maintenance_enabled'))
                ->helperText(__('settings::settings.help.maintenance_enabled')),
            TranslatableTabs::make(fn (string $locale): array => [
                Textarea::make("message.{$locale}")->label(__('settings::settings.fields.maintenance_message'))
                    ->placeholder(__('frontend.maintenance', locale: $locale))->rows(3)->maxLength(1000),
            ], 'maintenance-message'),
        ];
    }
}
