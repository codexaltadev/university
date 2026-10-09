<?php

namespace Modules\Fee;

use Modules\Fee\Filament\FeePlugin;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Settings\FeeSettings;
use Modules\Fee\Tabs\TuitionTab;
use Modules\Program\Models\Program;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class FeeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FeePlugin::class);
        $r->scopeable(Tuition::class);                                   // through the program
        $r->entityTab(Program::class, TuitionTab::class);                // FR-FEE-04, FR-PROG-18
        $r->layouts('fee.tuition-table', ['grouped', 'compact']);        // FR-FEE-03
        $r->settings(FeeSettings::class);                                // Settings › Fees: waiver mode, disclaimer
    }
}
