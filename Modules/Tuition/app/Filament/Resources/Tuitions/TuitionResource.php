<?php

namespace Modules\Tuition\Filament\Resources\Tuitions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Tuition\Filament\Clusters\TuitionFees;
use Modules\Tuition\Filament\Resources\Tuitions\Pages\CreateTuition;
use Modules\Tuition\Filament\Resources\Tuitions\Pages\EditTuition;
use Modules\Tuition\Filament\Resources\Tuitions\Pages\ListTuitions;
use Modules\Tuition\Filament\Resources\Tuitions\Schemas\TuitionForm;
use Modules\Tuition\Filament\Resources\Tuitions\Tables\TuitionsTable;
use Modules\Tuition\Models\Tuition;
use Platform\Filament\RelationManagers\ActivityRelationManager;

/** Academic › Tuition fees (M-10, FR-FEE-02). */
class TuitionResource extends Resource
{
    protected static ?string $model = Tuition::class;

    protected static ?string $cluster = TuitionFees::class;   // Academic › Tuition Fees

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('tuition::tuition.tuition.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tuition::tuition.tuition.plural');
    }

    /** Trash tab and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return TuitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TuitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTuitions::route('/'),
            'create' => CreateTuition::route('/create'),
            'edit' => EditTuition::route('/{record}/edit'),
        ];
    }
}
