<?php

namespace Modules\Waiver\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Academic › Waiver Calculator (UI/UX Part A §3): the module's own screens — waiver rules,
 * waiver categories, boards — in one sub-menu. Programs, levels, faculties, tuitions and
 * genders come from the other modules (the dependencies), not entered here.
 */
class WaiverCalculator extends Cluster
{
    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'waiver-calculator';

    public static function getNavigationLabel(): string
    {
        return __('waiver::waiver.calculator.title');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('waiver::waiver.calculator.title');
    }
}
