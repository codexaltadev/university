<?php

namespace Modules\Faq;

use Modules\Faq\Blocks\FaqBlock;
use Modules\Faq\Filament\FaqPlugin;
use Modules\Faq\Freshness\FaqFreshness;
use Modules\Faq\Metrics\FaqCount;
use Modules\Faq\Models\Faq;
use Modules\Faq\Sections\FaqHighlightsSection;
use Modules\Faq\Settings\FaqSettings;
use Modules\Faq\Website\ProgramFaqTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class FaqExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FaqPlugin::class);
        $r->attachable(Faq::class);   // "Show in" any entity, main site, highlight
        $r->settings(FaqSettings::class);   // Settings › FAQ tab
        $r->vocabulary(Faq::CATEGORY, label: 'FAQ category', hierarchical: true, multiple: false);
        $r->metric('faq.count', FaqCount::class);   // Statistics counter
        $r->homepageSection(FaqHighlightsSection::class);
        $r->layouts('faq.listing', ['accordion', 'grouped']);   // Appearance › Layouts (FR-THEME-08); first = default
        $r->freshnessSource(FaqFreshness::class);               // Content Update Reminder (FR-NOTI-10)
        $r->websitePage('faq.index', label: 'faq::faq.title', path: '/'.Faq::PATH);   // Menus › Module page; /{entity}/faq too
        $r->searchable(Faq::class, label: 'faq::faq.title', weight: 4);              // website search (FR-SRCH-01)
        $r->pageBlock(FaqBlock::class);                                               // "FAQ" on any page (FR-FAQ-03)

        $program = 'Modules\\Program\\Models\\Program';
        if (class_exists($program)) {
            $r->entityTab($program, ProgramFaqTab::class);   // "FAQ" on a program's page (FR-FAQ-03); Program is optional
        }
    }
}
