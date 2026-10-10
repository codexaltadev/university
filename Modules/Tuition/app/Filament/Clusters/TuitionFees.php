<?php

namespace Modules\Tuition\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Academic › Tuition Fees (UI/UX Part A §3): the tuition team's screens — tuition fees and
 * tuition categories — in one sub-menu. Programs, departments and levels come from the
 * other modules (the dependencies), not entered here.
 */
class TuitionFees extends Cluster
{
    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 39;

    protected static ?string $slug = 'tuition';

    public static function getNavigationLabel(): string
    {
        return __('tuition::tuition.tuition_fees');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('tuition::tuition.tuition_fees');
    }
}
