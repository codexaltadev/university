<?php

namespace Modules\Fee\Filament\Hub;

use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Fee\Filament\Resources\Tuitions\Tables\TuitionsTable;
use Modules\Fee\Filament\Resources\Tuitions\TuitionResource;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Platform\Filament\Hub\HubRelationPage;

/**
 * "Tuition Fees" in the Program Hub (FR-GEN-86, UI/UX Part A §4.3, group "Fees"): this
 * program's tuitions. Whoever may view tuitions sees them (entity users by scope); adding
 * and editing follow the tuition permissions — by default the Admission Office.
 */
class ProgramTuitions extends HubRelationPage
{
    protected static string $resource = ProgramResource::class;

    protected static string $relationship = 'tuitions';

    protected static ?string $relatedResource = TuitionResource::class;

    protected static ?int $navigationSort = 40;

    public static function hubKey(): string
    {
        return 'tuition-fees';
    }

    protected static function parentQueryKey(): ?string
    {
        return 'program';
    }

    public static function getNavigationLabel(): string
    {
        return __('fee::fee.tuition_fees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('program::program.hub.fees');
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedBanknotes;
    }

    public function table(Table $table): Table
    {
        return TuitionsTable::configure($table, inProgram: true)
            ->headerActions([
                CreateAction::make()->url(fn (): string => TuitionResource::getUrl('create', $this->parentQuery())),
            ]);
    }
}
