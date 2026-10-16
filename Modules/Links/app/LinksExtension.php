<?php

namespace Modules\Links;

use Modules\Links\Filament\LinksPlugin;
use Modules\Links\Footer\LinksFooterBlock;
use Modules\Links\Models\Link;
use Modules\Links\Notifications\LinksBroken;
use Modules\Links\Sections\ImportantLinksSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class LinksExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(LinksPlugin::class);                                               // Content › Important Links
        $r->attachable(Link::class);                                                          // each place its own; main site (Admin)
        $r->vocabulary(Link::GROUP, label: 'Link group', hierarchical: false, multiple: false);
        $r->homepageSection(ImportantLinksSection::class);                                    // FR-LINK-02
        $r->footerBlock(LinksFooterBlock::class);                                             // FR-LINK-02, in Header & Footer
        $r->websitePage('links.index', label: 'links::links.plural', path: '/'.Link::PATH);
        $r->notification(LinksBroken::class);                                                 // FR-LINK-03
        $r->layouts('links.listing', ['tiles', 'list']);
    }
}
