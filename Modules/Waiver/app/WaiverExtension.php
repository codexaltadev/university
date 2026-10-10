<?php

namespace Modules\Waiver;

use Modules\Waiver\Filament\WaiverPlugin;
use Modules\Waiver\Settings\WaiverSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class WaiverExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(WaiverPlugin::class);                         // Academic › Waiver Calculator: rules, categories, boards
        $r->settings(WaiverSettings::class);                             // waiver mode, disclaimer
    }
}
