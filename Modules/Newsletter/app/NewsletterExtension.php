<?php

namespace Modules\Newsletter;

use Modules\Newsletter\Filament\NewsletterPlugin;
use Modules\Newsletter\Footer\SubscribeFooterBlock;
use Modules\Newsletter\Settings\NewsletterSettings;
use Modules\Newsletter\Support\NewsletterWebsite;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class NewsletterExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(NewsletterPlugin::class);                                                     // Communication › Campaigns, Subscribers, Lists
        $r->footerBlock(SubscribeFooterBlock::class);                                                    // FR-NLT-01, in Header & Footer
        $r->websitePage('newsletter.index', label: 'newsletter::newsletter.title', path: '/'.NewsletterWebsite::PATH);
        $r->settings(NewsletterSettings::class);                                                         // per minute, page line, email note
    }
}
