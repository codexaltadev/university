<?php

namespace Modules\Notice;

use Modules\Notice\Filament\NoticePlugin;
use Modules\Notice\Freshness\NoticeFreshness;
use Modules\Notice\Metrics\NoticeCount;
use Modules\Notice\Models\Notice;
use Modules\Notice\Sections\NoticeBoardSection;
use Modules\Notice\Sections\NoticeTickerSection;
use Modules\Notice\Settings\NoticeSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class NoticeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(NoticePlugin::class);                                                   // Content › Notices
        $r->attachable(Notice::class);                                                             // "Show in", main site (Admin), highlight
        $r->vocabulary(Notice::CATEGORY, label: 'Notice category', hierarchical: true, multiple: false);
        $r->settings(NoticeSettings::class);                                                       // "New" badge days, per page
        $r->metric('notice.count', NoticeCount::class);
        $r->homepageSection(NoticeBoardSection::class);                                            // FR-NOT-06
        $r->homepageSection(NoticeTickerSection::class);
        $r->freshnessSource(NoticeFreshness::class);
        $r->websitePage('notice.index', label: 'notice::notice.plural', path: '/'.Notice::PATH);    // also /{entity}/notices
        $r->searchable(Notice::class, label: 'notice::notice.plural', weight: 7);
        $r->sitemap(Notice::class);
        $r->menuItemType(Notice::class);
        $r->layouts('notice.listing', ['table', 'list', 'cards']);                                 // FR-NOT-04; first = default
    }
}
