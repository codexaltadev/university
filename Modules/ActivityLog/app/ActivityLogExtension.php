<?php

namespace Modules\ActivityLog;

use Modules\ActivityLog\Filament\ActivityLogPlugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class ActivityLogExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(ActivityLogPlugin::class);
    }
}
