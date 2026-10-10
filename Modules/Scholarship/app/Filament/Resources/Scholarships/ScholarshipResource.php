<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Scholarship\Filament\Resources\Scholarships\Pages\CreateScholarship;
use Modules\Scholarship\Filament\Resources\Scholarships\Pages\EditScholarship;
use Modules\Scholarship\Filament\Resources\Scholarships\Pages\ListScholarships;
use Modules\Scholarship\Filament\Resources\Scholarships\Schemas\ScholarshipForm;
use Modules\Scholarship\Filament\Resources\Scholarships\Tables\ScholarshipsTable;
use Modules\Scholarship\Models\Scholarship;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Scholarships (M-10, FR-FEE-10). */
class ScholarshipResource extends Resource
{
    protected static ?string $model = Scholarship::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('scholarship::scholarship.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('scholarship::scholarship.plural');
    }

    /** Trash and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return ScholarshipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScholarshipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScholarships::route('/'),
            'create' => CreateScholarship::route('/create'),
            'edit' => EditScholarship::route('/{record}/edit'),
        ];
    }
}
