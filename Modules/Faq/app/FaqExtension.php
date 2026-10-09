<?php

namespace Modules\Faq;

use Modules\Faq\Filament\FaqPlugin;
use Modules\Faq\Metrics\FaqCount;
use Modules\Faq\Models\Faq;
use Modules\Faq\Sections\FaqHighlightsSection;
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
        $r->vocabulary('faq.category', label: 'FAQ category', hierarchical: true, multiple: false);
        $r->metric('faq.count', FaqCount::class);   // Statistics counter
        $r->homepageSection(FaqHighlightsSection::class);
        $r->layouts('faq.listing', ['accordion', 'grouped']);   // Appearance › Layouts (FR-THEME-08); first = default
    }
}
