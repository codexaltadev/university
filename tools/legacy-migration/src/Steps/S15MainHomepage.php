<?php

namespace Legacy\Migration\Steps;

use Modules\Homepage\Support\Homepages;
use Platform\Relations\RelationEngine;

/**
 * S15H Main homepage (Data Migration Plan S15, owner 2026-10-17: the default theme is a copy of
 * daffodilvarsity.edu.bd). The live homepage is put together in code, so its order is set here
 * with the default theme's sections (theme.json "sections"): hero, rankings / vision / mission,
 * find your degree, scholarships, research, recent events, distinguished visitors, what people say
 * (testimonials, owner 2026-10-25), alumni stories, the
 * alumni portal button. Their texts are the theme's defaults until an editor changes them.
 * Running it again puts the main homepage back to this list.
 */
class S15MainHomepage extends Step
{
    /** [section type, settings] in DIU's order */
    public const SECTIONS = [
        ['theme.hero', []],
        ['theme.mission', ['show' => 'rankings', 'count' => 8]],
        ['theme.degree', []],
        ['theme.scholarships', []],
        ['theme.research', []],
        ['theme.events', ['source_mode' => 'latest', 'count' => 3]],
        ['theme.visitors', ['source_mode' => 'highlighted', 'count' => 10]],   // DIU's "distinguished" slider = its chosen visitors (is_home, S18I)
        ['theme.testimonials', ['source_mode' => 'highlighted', 'count' => 6]],   // "What People Say": the chosen testimonials (owner 2026-10-25; S17M picks a first set)
        ['theme.alumni-news', ['source_mode' => 'highlighted_first', 'count' => 5]],
        ['theme.link-button', []],
    ];

    public static function key(): string
    {
        return 'S15H';
    }

    public static function title(): string
    {
        return 'Main homepage (live site order)';
    }

    public static function requires(): array
    {
        return ['homepage'];
    }

    protected function handle(): void
    {
        $homepages = app(Homepages::class);
        $layout = $homepages->own(RelationEngine::SITE_KEY) ?? $homepages->create(RelationEngine::SITE_KEY);
        $created = $layout->wasRecentlyCreated;
        $catalogue = $homepages->catalogue();

        $layout->sections()->get()->each->forceDelete();
        foreach (self::SECTIONS as $position => [$type, $settings]) {
            if (! isset($catalogue[$type])) {
                $this->report->skipped('home_sections', "{$type}: not offered by the active theme or its module is off");

                continue;
            }
            $layout->sections()->create(['section_type' => $type, 'settings' => $settings, 'sort_order' => $position + 1, 'is_active' => true]);
            $this->report->saved('home_sections', true);
        }

        $layout->update(['is_active' => true]);
        Homepages::flush();
        $this->report->saved('home_layouts', $created);
        $this->report->review('Texts come from the default theme (theme.json "sections" defaults, taken from the live site); the hero video and the partner logos point to the live site\'s files until they are uploaded to the Media Library.');
    }
}
