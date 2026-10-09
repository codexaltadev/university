<?php

namespace Modules\Localization\Filament\Resources\Languages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Localization\Filament\Resources\Languages\Pages\ManageLanguages;
use Modules\Localization\Filament\Resources\Languages\Schemas\LanguageForm;
use Modules\Localization\Filament\Resources\Languages\Tables\LanguagesTable;
use Platform\Localization\Language;

/** Site › Languages (C-15, FR-LANG-01). */
class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 91;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getModelLabel(): string
    {
        return __('localization::localization.language');
    }

    public static function getPluralModelLabel(): string
    {
        return __('localization::localization.languages');
    }

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLanguages::route('/'),
        ];
    }
}
