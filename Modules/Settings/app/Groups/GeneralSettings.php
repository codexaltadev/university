<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Localization\Languages;
use Platform\Settings\ModuleSettings;

/** FR-SET-01: names, founding year, timezone, website date format. */
class GeneralSettings extends ModuleSettings
{
    public const DATE_FORMATS = ['d M Y' => '09 Oct 2026', 'd F Y' => '09 October 2026', 'F j, Y' => 'October 9, 2026', 'd/m/Y' => '09/10/2026', 'Y-m-d' => '2026-10-09'];

    public function group(): string
    {
        return 'general';
    }

    public function label(): string
    {
        return __('settings::settings.groups.general');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedHome;
    }

    public function sort(): int
    {
        return 10;
    }

    public function translatable(): array
    {
        return ['site_name', 'tagline', 'university_name'];
    }

    public function defaults(): array
    {
        return [
            'site_name' => [app(Languages::class)->default() => config('app.name')],
            'timezone' => config('app.timezone'),
            'date_format' => 'd M Y',
        ];
    }

    public function schema(): array
    {
        return [
            TranslatableTabs::make()->schema(fn (string $locale, bool $default): array => [
                TextInput::make("site_name.{$locale}")->label(__('settings::settings.fields.site_name'))->required($default)->maxLength(150),
                TextInput::make("tagline.{$locale}")->label(__('settings::settings.fields.tagline'))->maxLength(255),
                TextInput::make("university_name.{$locale}")->label(__('settings::settings.fields.university_name'))->maxLength(255),
            ]),
            TextInput::make('established_year')->label(__('settings::settings.fields.established_year'))
                ->numeric()->minValue(1000)->maxValue((int) date('Y')),
            Select::make('timezone')->label(__('settings::settings.fields.timezone'))
                ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                ->searchable()->required(),
            Select::make('date_format')->label(__('settings::settings.fields.date_format'))
                ->options(self::DATE_FORMATS)->required()->selectablePlaceholder(false),
        ];
    }
}
