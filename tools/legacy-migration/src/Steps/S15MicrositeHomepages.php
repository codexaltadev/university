<?php

namespace Legacy\Migration\Steps;

use Modules\Homepage\Support\Homepages;
use Platform\Context\MicrositeOwners;

/**
 * S15K Microsite homepages (Data Migration Plan S15, Phase 4.4b). The live site draws every
 * department and every faculty page with the same code, so each kind gets one shared homepage
 * ("All Departments", "All Faculties") in the live order, built from the default theme's sections;
 * a department or faculty that needs its own can still start one from it. Running it again puts
 * the shared homepages back to these lists; own homepages are left alone.
 */
class S15MicrositeHomepages extends Step
{
    /** kind alias => [section type, settings] in DIU's order */
    public const KINDS = [
        'department.department' => [
            ['theme.page-hero', []],
            ['theme.programs', []],
            ['theme.notices', []],
            ['theme.labs', []],
            ['theme.partners', []],
            ['theme.ambitions', ['count' => 4]],
            ['theme.microsite-news', ['source_mode' => 'latest', 'count' => 5]],
            ['theme.event-cards', ['count' => 3]],
            ['theme.campus-services', []],
        ],
        'faculty.faculty' => [
            ['theme.page-hero', ['content' => ['about_right' => true]]],
            ['theme.faculty-stats', ['set' => 'default']],
            ['theme.faculty-departments', ['limit' => 24]],
            ['theme.labs', []],
            ['theme.partners', ['content' => ['grey' => false, 'space_below' => true, 'partners_button_label' => ['en' => 'Division of Research, DIU']]]],
            ['theme.ambitions', ['count' => 4]],
            ['theme.microsite-news', ['source_mode' => 'latest', 'count' => 5]],
            ['theme.event-cards', ['count' => 3, 'content' => ['link_url' => '/events']]],
            ['theme.campus-services', []],
        ],
    ];

    public static function key(): string
    {
        return 'S15K';
    }

    public static function title(): string
    {
        return 'Department and faculty homepages (live site order)';
    }

    public static function requires(): array
    {
        return ['homepage'];
    }

    protected function handle(): void
    {
        $homepages = app(Homepages::class);
        $catalogue = $homepages->catalogue();

        foreach (self::KINDS as $alias => $sections) {
            if (! in_array($alias, MicrositeOwners::kinds(), true)) {
                $this->report->skipped('home_layouts', "{$alias}: module is off");

                continue;
            }
            $owner = MicrositeOwners::kindKey($alias);
            $layout = $homepages->own($owner) ?? $homepages->create($owner);
            $created = $layout->wasRecentlyCreated;

            $layout->sections()->get()->each->forceDelete();
            foreach ($sections as $position => [$type, $settings]) {
                if (! isset($catalogue[$type])) {
                    $this->report->skipped('home_sections', "{$owner} {$type}: not offered by the active theme or its module is off");

                    continue;
                }
                $layout->sections()->create(['section_type' => $type, 'settings' => $settings, 'sort_order' => $position + 1, 'is_active' => true]);
                $this->report->saved('home_sections', true);
            }

            $layout->update(['is_active' => true]);
            $this->report->saved('home_layouts', $created);
        }

        Homepages::flush();
        $this->report->review('Programs keep their module page until the program page is copied as theme sections; the faculty figures and headline are the live defaults — FBE\'s own (710+ publications …) belong in an own FBE homepage.');
    }
}
