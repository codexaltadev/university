<?php

namespace Modules\News;

use Modules\News\Blocks\NewsBlock;
use Modules\News\Filament\NewsPlugin;
use Modules\News\Freshness\NewsFreshness;
use Modules\News\Metrics\NewsCount;
use Modules\News\Models\Post;
use Modules\News\Sections\FeaturedNewsSection;
use Modules\News\Sections\LatestNewsSection;
use Modules\News\Sections\NewsByCategorySection;
use Modules\News\Settings\NewsSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class NewsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(NewsPlugin::class);                                                   // Content › News
        $r->attachable(Post::class);                                                             // "Show in", main site (Admin), highlight
        $r->vocabulary(Post::CATEGORY, label: 'News category', hierarchical: true, multiple: false);
        $r->settings(NewsSettings::class);                                                       // per page, show author
        $r->metric('news.count', NewsCount::class);                                              // Statistics counter
        $r->homepageSection(LatestNewsSection::class);                                           // FR-NEWS-04
        $r->homepageSection(FeaturedNewsSection::class);
        $r->homepageSection(NewsByCategorySection::class);
        $r->pageBlock(NewsBlock::class);
        $r->freshnessSource(NewsFreshness::class);                                               // Content Update Reminder
        $r->websitePage('news.index', label: 'news::news.plural', path: '/'.Post::PATH);         // also /{entity}/news
        $r->searchable(Post::class, label: 'news::news.plural', weight: 6);
        $r->sitemap(Post::class);
        $r->menuItemType(Post::class);
        $r->layouts('news.listing', ['grid', 'list', 'cards', 'magazine']);                      // FR-NEWS-03; first = default
        $r->layouts('news.detail', ['standard', 'full_width']);
    }
}
