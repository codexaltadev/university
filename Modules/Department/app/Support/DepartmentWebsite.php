<?php

namespace Modules\Department\Support;

use Illuminate\Http\Response;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Theme\LayoutResolver;

/**
 * Department on the website (FR-DEPT-06/09): the listing /departments grouped by
 * Faculty (?faculty={short name} shows one), in the layout chosen in Appearance ›
 * Layouts (grid, list, card). The microsite /department/{slug} comes from the kernel.
 */
class DepartmentWebsite
{
    public const LISTING = 'departments';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if ($context->isMicrosite() || trim($rest, '/') !== self::LISTING) {
            return null;
        }

        $layouts = app(LayoutResolver::class);
        $layout = $layouts->for('department.listing') ?? 'grid';

        $faculties = Faculty::query()->frontendVisible()->ordered()->get();
        $current = $faculties->first(fn (Faculty $f): bool => strcasecmp($f->short_name, (string) request()->query('faculty')) === 0);

        $position = $faculties->pluck('id')->flip();   // by Faculty in its order, then the departments' own order
        $listed = Department::query()->listed()->with('faculty')->get()
            ->sortBy(fn (Department $department): int => $position[$department->faculty_id] ?? PHP_INT_MAX)->values();
        $departments = $current ? $listed->where('faculty_id', $current->id) : $listed;

        app(Seo::class)->set(['title' => __('department::department.plural'), 'path' => '/'.self::LISTING]);

        return response()->view("department::listing.{$layout}", [
            'groups' => $faculties->map(fn (Faculty $faculty): array => ['faculty' => $faculty, 'departments' => $departments->where('faculty_id', $faculty->id)->values()])
                ->filter(fn (array $group): bool => $group['departments']->isNotEmpty())->values(),
            'faculties' => $faculties,
            'listed' => $listed,
            'currentFaculty' => $current,
            'options' => $layouts->options('department.listing'),
        ]);
    }

    /**
     * FR-DEPT-05: a new Department starts with its own counters — Programs and Faculty
     * Members (counted by those modules once active) and Students (manual).
     */
    public static function createStatSet(Department $department): void
    {
        $key = $department->getMorphClass().':'.$department->getKey();

        if (StatSet::query()->ownedBy($key)->where('key', 'default')->exists()) {
            return;
        }

        $set = (new StatSet(['key' => 'default', 'name' => ['en' => 'Department counters', 'bn' => 'Department-এর সংখ্যা']]))->setOwnerKey($key);
        $set->save();

        foreach ([
            ['label' => ['en' => 'Programs', 'bn' => 'Program'], 'source' => 'metric', 'metric_key' => 'program.count'],
            ['label' => ['en' => 'Faculty Members', 'bn' => 'শিক্ষক'], 'source' => 'metric', 'metric_key' => 'faculty-member.count'],
            ['label' => ['en' => 'Students', 'bn' => 'শিক্ষার্থী'], 'source' => 'manual', 'rounding' => 'plus'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
