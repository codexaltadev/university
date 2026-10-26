<?php

namespace Modules\Seo;

use Modules\Seo\Filament\SeoPlugin;
use Modules\Seo\Settings\SearchSettings;
use Modules\Seo\Settings\SeoSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class SeoExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(SeoPlugin::class);
        $r->settings(SeoSettings::class);   // Settings › SEO
        $r->settings(SearchSettings::class);   // Settings › Search: synonyms, popular searches
    }
}
