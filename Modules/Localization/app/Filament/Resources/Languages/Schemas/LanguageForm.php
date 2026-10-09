<?php

namespace Modules\Localization\Filament\Resources\Languages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Platform\Localization\Language;
use Platform\Localization\Languages;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label(__('localization::localization.fields.code'))
                ->helperText(__('localization::localization.help.code'))
                ->required()->maxLength(10)->regex('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/i')
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Language $record): bool => $record !== null)   // URL prefix and JSON key: fixed once created
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                    $known = Languages::KNOWN[strtolower((string) $state)] ?? null;
                    if ($known && blank($get('name'))) {
                        $set('name', $known[0]);
                        $set('native_name', $known[1]);
                    }
                }),
            Select::make('direction')->label(__('localization::localization.fields.direction'))
                ->options(['ltr' => __('localization::localization.direction.ltr'), 'rtl' => __('localization::localization.direction.rtl')])
                ->default('ltr')->required()->selectablePlaceholder(false),
            TextInput::make('name')->label(__('localization::localization.fields.name'))->required()->maxLength(50),
            TextInput::make('native_name')->label(__('localization::localization.fields.native_name'))->required()->maxLength(50),
            Toggle::make('is_active')->label(__('localization::localization.fields.active'))->default(true)
                ->disabled(fn (?Language $record): bool => (bool) $record?->is_default)
                ->helperText(__('localization::localization.help.inactive')),
            Toggle::make('use_native_digits')->label(__('localization::localization.fields.native_digits'))
                ->helperText(__('localization::localization.help.native_digits')),
        ]);
    }
}
