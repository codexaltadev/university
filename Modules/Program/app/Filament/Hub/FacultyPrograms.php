<?php

namespace Modules\Program\Filament\Hub;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Filament\Resources\Programs\Tables\ProgramsTable;
use Platform\Filament\Hub\HubRelationPage;

/**
 * "Programs" in the Faculty Hub (FR-GEN-86): every program of the Faculty's departments,
 * scope-filtered. New programs are made in a Department's Hub (they need a Department).
 */
class FacultyPrograms extends HubRelationPage
{
    protected static string $resource = FacultyResource::class;

    protected static string $relationship = 'programs';

    protected static ?string $relatedResource = ProgramResource::class;

    protected static ?int $navigationSort = 30;

    public static function hubKey(): string
    {
        return 'programs';
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
        return ProgramsTable::configure($table, within: 'faculty')->reorderable(false)->headerActions([]);
    }
}
