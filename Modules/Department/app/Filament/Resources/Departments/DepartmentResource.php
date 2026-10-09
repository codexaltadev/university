<?php

namespace Modules\Department\Filament\Resources\Departments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Department\Filament\Resources\Departments\Pages\CreateDepartment;
use Modules\Department\Filament\Resources\Departments\Pages\EditDepartment;
use Modules\Department\Filament\Resources\Departments\Pages\ListDepartments;
use Modules\Department\Filament\Resources\Departments\Schemas\DepartmentForm;
use Modules\Department\Filament\Resources\Departments\Tables\DepartmentsTable;
use Modules\Department\Models\Department;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Departments (M-02). */
class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'short_name';

    public static function getModelLabel(): string
    {
        return __('department::department.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('department::department.plural');
    }

    /** Trash tab and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return DepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
