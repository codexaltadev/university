<?php

namespace Modules\Statistics;

use Modules\Statistics\Filament\Pages\ManageStatistics;
use Modules\Statistics\Filament\StatisticsPlugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class StatisticsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(StatisticsPlugin::class);
        $r->micrositeTool(ManageStatistics::class);   // "Microsite" button, "Working in" (UI/UX Part A §4.1)
    }
}
