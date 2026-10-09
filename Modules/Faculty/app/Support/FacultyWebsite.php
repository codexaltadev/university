<?php

namespace Modules\Faculty\Support;

use Illuminate\Http\Response;
use Modules\Faculty\Models\Faculty;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Theme\LayoutResolver;

/**
 * Faculty on the website (FR-FAC-05/07): the listing /faculties in the layout
 * chosen in Appearance › Layouts (grid, list, card). The microsite /faculty/{slug}
 * itself comes from the kernel (microsite entity + homepage).
 */
class FacultyWebsite
{
    public const LISTING = 'faculties';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if ($context->isMicrosite() || trim($rest, '/') !== self::LISTING) {
            return null;
        }

        $layouts = app(LayoutResolver::class);
        $layout = $layouts->for('faculty.listing') ?? 'grid';

        app(Seo::class)->set(['title' => __('faculty::faculty.plural'), 'path' => '/'.self::LISTING]);

        return response()->view("faculty::listing.{$layout}", [
            'faculties' => Faculty::query()->listed()->get(),
            'options' => $layouts->options('faculty.listing'),
        ]);
    }

    /**
     * FR-FAC-04, AC-FAC-02: a new Faculty starts with its own counters — Departments,
     * Programs, Faculty Members (counted by those modules once active) and Students (manual).
     */
    public static function createStatSet(Faculty $faculty): void
    {
        $key = $faculty->getMorphClass().':'.$faculty->getKey();

        if (StatSet::query()->ownedBy($key)->where('key', 'default')->exists()) {
            return;
        }

        $set = (new StatSet(['key' => 'default', 'name' => ['en' => 'Faculty counters', 'bn' => 'Faculty-র সংখ্যা']]))->setOwnerKey($key);
        $set->save();

        foreach ([
            ['label' => ['en' => 'Departments', 'bn' => 'বিভাগ'], 'source' => 'metric', 'metric_key' => 'department.count'],
            ['label' => ['en' => 'Programs', 'bn' => 'Program'], 'source' => 'metric', 'metric_key' => 'program.count'],
            ['label' => ['en' => 'Faculty Members', 'bn' => 'শিক্ষক'], 'source' => 'metric', 'metric_key' => 'faculty-member.count'],
            ['label' => ['en' => 'Students', 'bn' => 'শিক্ষার্থী'], 'source' => 'manual', 'rounding' => 'plus'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
