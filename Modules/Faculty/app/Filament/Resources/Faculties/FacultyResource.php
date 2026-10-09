<?php

namespace Modules\Faculty\Filament\Resources\Faculties;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Faculty\Filament\Resources\Faculties\Pages\CreateFaculty;
use Modules\Faculty\Filament\Resources\Faculties\Pages\EditFaculty;
use Modules\Faculty\Filament\Resources\Faculties\Pages\ListFaculties;
use Modules\Faculty\Filament\Resources\Faculties\Schemas\FacultyForm;
use Modules\Faculty\Filament\Resources\Faculties\Tables\FacultiesTable;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Faculties (M-01). */
class FacultyResource extends Resource
{
    protected static ?string $model = Faculty::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'short_name';

    public static function getModelLabel(): string
    {
        return __('faculty::faculty.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('faculty::faculty.plural');
    }

    /** Trash tab and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return FacultyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacultiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaculties::route('/'),
            'create' => CreateFaculty::route('/create'),
            'edit' => EditFaculty::route('/{record}/edit'),
        ];
    }
}
