<?php

namespace Modules\Seo\Filament\Resources\Redirects;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Seo\Filament\Resources\Redirects\Pages\CreateRedirect;
use Modules\Seo\Filament\Resources\Redirects\Pages\EditRedirect;
use Modules\Seo\Filament\Resources\Redirects\Pages\ListRedirects;
use Modules\Seo\Filament\Resources\Redirects\Schemas\RedirectForm;
use Modules\Seo\Filament\Resources\Redirects\Tables\RedirectsTable;
use Modules\Seo\Models\Redirect;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Site › Redirects (C-12, FR-SEO-04/05/09). */
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'source_path';

    public static function getModelLabel(): string
    {
        return __('seo::seo.redirects.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('seo::seo.redirects.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return RedirectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedirectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}
