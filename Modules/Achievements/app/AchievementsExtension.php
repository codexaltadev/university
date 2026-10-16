<?php

namespace Modules\Achievements;

use Modules\Achievements\Filament\AchievementsPlugin;
use Modules\Achievements\Metrics\AchievementsCount;
use Modules\Achievements\Models\Achievement;
use Modules\Achievements\Sections\RecentAchievementsSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class AchievementsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(AchievementsPlugin::class);                                                  // Content › Achievements
        $r->attachable(Achievement::class);                                                             // "Show in", main site (Admin), highlight
        $r->assignable(Achievement::class, roles: [Achievement::ROLE]);                                 // the achievers (FR-ACH-01)
        $r->vocabulary(Achievement::CATEGORY, label: 'Achievement category', hierarchical: true, multiple: false);
        $r->metric('achievements.count', AchievementsCount::class);                                     // "100+ International Awards" (FR-ACH-03)
        $r->homepageSection(RecentAchievementsSection::class);
        $r->websitePage('achievements.index', label: 'achievements::achievements.plural', path: '/'.Achievement::PATH);
        $r->searchable(Achievement::class, label: 'achievements::achievements.plural', weight: 5);
        $r->sitemap(Achievement::class);
        $r->menuItemType(Achievement::class);
        $r->layouts('achievements.listing', ['cards', 'timeline', 'grid']);                             // FR-ACH-02; first = default
    }
}
