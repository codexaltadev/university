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
use Modules\Program\Filament\Resources\Programs\Pages\ViewProgram;
use Modules\Program\Filament\Resources\Programs\Schemas\ProgramForm;
use Modules\Program\Filament\Resources\Programs\Tables\ProgramsTable;
use Modules\Program\Models\Program;
use Platform\Filament\Hub\EntityHub;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Programs (M-03). */
class ProgramResource extends Resource
{
    use EntityHub;

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
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    /** The Program Hub (FR-GEN-86): the Program editor (every part of the program page) and parts of other modules (Tuition Fees …). */
    protected static function hubBasePages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'view' => ViewProgram::route('/{record}'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }

    protected static function hubProfilePage(): string
    {
        return EditProgram::class;
    }

    protected static function hubProfilePages(): array
    {
        return [EditProgram::class, ViewProgram::class];
    }
}
