<?php

namespace Modules\Department\Filament\Hub;

use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Tables\DepartmentsTable;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Platform\Filament\Hub\HubRelationPage;

/**
 * "Departments" in the Faculty Hub (FR-GEN-86): this Faculty's departments, scope-filtered;
 * "New department" opens the department form with this Faculty preset and fixed.
 */
class FacultyDepartments extends HubRelationPage
{
    protected static string $resource = FacultyResource::class;

    protected static string $relationship = 'departments';

    protected static ?string $relatedResource = DepartmentResource::class;

    protected static ?int $navigationSort = 20;

    public static function hubKey(): string
    {
        return 'departments';
    }

    protected static function parentQueryKey(): ?string
    {
        return 'faculty';
    }

    public static function getNavigationLabel(): string
    {
        return __('department::department.plural');
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedBuildingOffice2;
    }

    public function table(Table $table): Table
    {
        return DepartmentsTable::configure($table, inFaculty: true)
            ->headerActions([
                CreateAction::make()->url(fn (): string => DepartmentResource::getUrl('create', $this->parentQuery())),
            ]);
    }
}
