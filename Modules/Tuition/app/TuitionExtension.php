<?php

namespace Modules\Tuition;

use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Parts\ProgramTuitionPart;
use Modules\Tuition\Filament\TuitionPlugin;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Tabs\TuitionTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class TuitionExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(TuitionPlugin::class);                        // Academic › Tuition Fees: tuition fees, tuition categories
        $r->scopeable(Tuition::class);                                   // through the program
        $r->entityTab(Program::class, TuitionTab::class);                // FR-FEE-04, FR-PROG-18
        $r->entityFormPart(Program::class, ProgramTuitionPart::class);   // read only, last part of the Program editor
        $r->layouts('tuition.listing', ['grouped', 'compact']);          // FR-FEE-03
    }
}
