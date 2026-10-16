<?php

namespace Modules\Office\Filament\Resources\Offices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Office\Filament\Resources\Offices\Pages\CreateOffice;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Filament\Resources\Offices\Pages\ListOffices;
use Modules\Office\Filament\Resources\Offices\RelationManagers\SectionsRelationManager;
use Modules\Office\Filament\Resources\Offices\RelationManagers\ServicesRelationManager;
use Modules\Office\Filament\Resources\Offices\RelationManagers\SupportContactsRelationManager;
use Modules\Office\Filament\Resources\Offices\Schemas\OfficeForm;
use Modules\Office\Filament\Resources\Offices\Tables\OfficesTable;
use Modules\Office\Models\Office;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('office::office.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('office::office.plural');
    }

    /** Trash and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return OfficeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfficesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [SectionsRelationManager::class, ServicesRelationManager::class, SupportContactsRelationManager::class, ActivityRelationManager::class];   // FR-OFF-04/06/07, FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffices::route('/'),
            'create' => CreateOffice::route('/create'),
            'edit' => EditOffice::route('/{record}/edit'),
        ];
    }
}
