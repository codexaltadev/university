<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Forms\TranslatableTabs;
use Platform\Settings\ModuleSettings;
use Platform\Settings\Settings;

/** FR-SET-02: main phone / email for header and footer, office hours, every campus. */
class ContactSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return __('settings::settings.groups.contact');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPhone;
    }

    public function sort(): int
    {
        return 20;
    }

    /** Campus names and addresses hold their languages inside each campus. */
    public function translatable(): array
    {
        return ['office_hours'];
    }

    public function defaults(): array
    {
        return ['campuses' => []];
    }

    public function schema(): array
    {
        return [
            TextInput::make('phone')->label(__('settings::settings.fields.phone'))->tel()->maxLength(50),
            TextInput::make('email')->label(__('settings::settings.fields.email'))->email()->maxLength(150),
            TranslatableTabs::make(fn (string $locale): array => [
                TextInput::make("office_hours.{$locale}")->label(__('settings::settings.fields.office_hours'))->maxLength(255),
            ], 'contact-hours'),
            Repeater::make('campuses')->label(__('settings::settings.fields.campuses'))
                ->schema([
                    ...TranslatableTabs::inline(fn (string $locale, bool $default, string $language): array => [
                        TextInput::make("name.{$locale}")->label(__('settings::settings.fields.campus_name')." ({$language})")->required($default)->maxLength(150),
                        Textarea::make("address.{$locale}")->label(__('settings::settings.fields.address')." ({$language})")->rows(2)->maxLength(500),
                    ]),
                    TagsInput::make('phones')->label(__('settings::settings.fields.phones')),
                    TagsInput::make('emails')->label(__('settings::settings.fields.emails')),
                    TextInput::make('map_embed')->label(__('settings::settings.fields.map_embed'))
                        ->url()->maxLength(2000)->helperText(__('settings::settings.help.map_embed'))->columnSpanFull(),
                ])
                ->columns(2)
                ->itemLabel(fn (array $state): ?string => app(Settings::class)->translate($state['name'] ?? null))
                ->collapsible()
                ->reorderable()
                ->defaultItems(0)
                ->columnSpanFull(),
        ];
    }
}
