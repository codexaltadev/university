<?php

namespace Modules\Settings\Groups;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Modules\Settings\Support\SettingsAccess;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/** FR-SET-08: visitors see a message (and, Phase 4.7h, how long it lasts and whom to write to); signed-in admins still see the website. */
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
        return ['message', 'duration'];
    }

    public function defaults(): array
    {
        return ['enabled' => false, 'duration' => null, 'help_email' => null];
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
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                Textarea::make("message.{$locale}")->label(__('settings::settings.fields.maintenance_message'))
                    ->placeholder(__('frontend.maintenance', locale: $locale))->rows(3)->maxLength(1000),
                TextInput::make("duration.{$locale}")->label(__('settings::settings.fields.maintenance_duration'))
                    ->placeholder(__('settings::settings.help.maintenance_duration', locale: $locale))->maxLength(150),
            ]),
            TextInput::make('help_email')->label(__('settings::settings.fields.maintenance_help_email'))->email()->maxLength(191),
        ];
    }
}
