<?php

namespace Modules\HeaderFooter;

use Modules\HeaderFooter\Filament\HeaderFooterPlugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class HeaderFooterExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(HeaderFooterPlugin::class);
    }
}
