<?php

namespace Modules\Admission;

use Modules\Admission\Blocks\ApplyBlock;
use Modules\Admission\Blocks\CircularsBlock;
use Modules\Admission\Blocks\ContactsBlock;
use Modules\Admission\Blocks\HighlightsBlock;
use Modules\Admission\Blocks\ResultLookupBlock;
use Modules\Admission\Blocks\StepsBlock;
use Modules\Admission\Blocks\SubscribeBlock;
use Modules\Admission\Filament\AdmissionPlugin;
use Modules\Admission\Models\AdmissionContactGroup;
use Modules\Admission\Models\AdmissionHighlight;
use Modules\Admission\Models\Circular;
use Modules\Admission\Sections\AdmissionGoingOnSection;
use Modules\Admission\Settings\AdmissionSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class AdmissionExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(AdmissionPlugin::class);                                            // Academic › Admission
        $r->websitePage('admission.result', label: 'admission::admission.result_page.title', path: '/admission/result');       // Menus › Module page
        $r->websitePage('admission.circulars', label: 'admission::admission.circular.plural', path: '/'.Circular::PATH);
        $r->assignable(AdmissionContactGroup::class, roles: [AdmissionContactGroup::ROLE]);   // contact people (FR-ADM-10)
        $r->searchable(Circular::class, label: 'admission::admission.circular.plural', weight: 6);
        $r->sitemap(Circular::class);
        $r->menuItemType(Circular::class);
        $r->vocabulary(AdmissionHighlight::CATEGORY, label: 'Admission information category', multiple: false);   // Notice, Facility, Campus Life
        foreach ([CircularsBlock::class, HighlightsBlock::class, StepsBlock::class, ContactsBlock::class, SubscribeBlock::class, ResultLookupBlock::class, ApplyBlock::class] as $block) {
            $r->pageBlock($block);                                                              // FR-ADM-15: on /admission and any page
        }
        $r->homepageSection(AdmissionGoingOnSection::class);                                   // FR-ADM-11
        $r->settings(AdmissionSettings::class);                                                // Apply / info / WhatsApp links
    }
}
