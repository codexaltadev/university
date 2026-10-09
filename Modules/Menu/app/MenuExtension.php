<?php

namespace Modules\Menu;

use Modules\Menu\Filament\MenuPlugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class MenuExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(MenuPlugin::class);
    }
}
