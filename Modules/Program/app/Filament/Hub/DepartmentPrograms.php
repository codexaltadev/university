<?php

namespace Modules\Program\Filament\Hub;

use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Filament\Resources\Programs\Tables\ProgramsTable;
use Platform\Filament\Hub\HubRelationPage;

/**
 * "Programs" in the Department Hub (FR-GEN-86): this Department's programs, scope-filtered;
 * "New program" opens the program form with this Department preset and fixed.
 */
class DepartmentPrograms extends HubRelationPage
{
    protected static string $resource = DepartmentResource::class;

    protected static string $relationship = 'programs';

    protected static ?string $relatedResource = ProgramResource::class;

    protected static ?int $navigationSort = 20;

    public static function hubKey(): string
    {
        return 'programs';
    }

    protected static function parentQueryKey(): ?string
    {
        return 'department';
    }

    public static function getNavigationLabel(): string
    {
        return __('program::program.plural');
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedAcademicCap;
    }

    public function table(Table $table): Table
    {
        return ProgramsTable::configure($table, within: 'department')
            ->headerActions([
                CreateAction::make()->url(fn (): string => ProgramResource::getUrl('create', $this->parentQuery())),
            ]);
    }
}
