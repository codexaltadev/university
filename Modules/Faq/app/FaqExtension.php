<?php

namespace Modules\Faq;

use Modules\Faq\Filament\FaqPlugin;
use Modules\Faq\Models\Faq;
use Modules\Faq\Settings\FaqSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class FaqExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FaqPlugin::class);
        $r->attachable(Faq::class);   // "Show in" any entity, main site, highlight
        $r->settings(FaqSettings::class);   // Settings › FAQ tab
    }
}
