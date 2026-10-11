<?php

namespace Modules\Admission\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Academic › Admission (UI/UX Part A §3): the Admission Office's screens — circulars,
 * information cards, steps, contacts, test results, subscribers — in one sub-menu.
 */
class AdmissionCluster extends Cluster
{
    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 38;

    protected static ?string $slug = 'admission';

    public static function getNavigationLabel(): string
    {
        return __('admission::admission.title');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('admission::admission.title');
    }
}
