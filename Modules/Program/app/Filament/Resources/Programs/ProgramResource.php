<?php

namespace Modules\Program\Filament\Resources\Programs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Program\Filament\Resources\Programs\Pages\CreateProgram;
use Modules\Program\Filament\Resources\Programs\Pages\EditProgram;
use Modules\Program\Filament\Resources\Programs\Pages\ListPrograms;
use Modules\Program\Filament\Resources\Programs\RelationManagers\CareerPathsRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\FacilitatorsRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\IndustriesRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\MajorsRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\MaterialsRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\MembershipsRelationManager;
use Modules\Program\Filament\Resources\Programs\RelationManagers\VariantsRelationManager;
use Modules\Program\Filament\Resources\Programs\Schemas\ProgramForm;
use Modules\Program\Filament\Resources\Programs\Tables\ProgramsTable;
use Modules\Program\Models\Program;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Programs (M-03). */
class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'short_name';

    public static function getModelLabel(): string
    {
        return __('program::program.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('program::program.plural');
    }

    /** Trash tab and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramsTable::configure($table);
    }

    public static function getRelations(): array
    {
        // BRL-PROG-04: sub-items are edited on the Program's page
        return [
            MajorsRelationManager::class,
            CareerPathsRelationManager::class,
            IndustriesRelationManager::class,
            FacilitatorsRelationManager::class,
            MaterialsRelationManager::class,
            MembershipsRelationManager::class,
            VariantsRelationManager::class,
            ActivityRelationManager::class,   // FR-GEN-83
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }
}
