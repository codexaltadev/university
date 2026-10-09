<?php

namespace Modules\Program\Support;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * Program on the website (FR-PROG-12): the listing /programs with filters
 * ?faculty=FSIT&department=CSE&level=graduate&shift=evening&mode=online&q=…
 * in the layout chosen in Appearance › Layouts (card, list, table, detailed).
 * The microsite /program/{slug} comes from the kernel.
 */
class ProgramWebsite
{
    public const LISTING = 'programs';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if ($context->isMicrosite() || trim($rest, '/') !== self::LISTING) {
            return null;
        }

        $layouts = app(LayoutResolver::class);
        $layout = $layouts->for('program.listing') ?? 'card';

        $choices = $this->choices($locale);
        $query = request()->query();
        $selected = [
            'faculty' => $this->match($choices['faculties'], $query['faculty'] ?? null, fn (Faculty $f) => $f->short_name),
            'department' => $this->match($choices['departments'], $query['department'] ?? null, fn (Department $d) => $d->short_name),
            'level' => $this->match($choices['levels'], $query['level'] ?? null, fn (Term $t) => $t->slug($locale)),
            'shift' => in_array($query['shift'] ?? null, Program::SHIFTS, true) ? $query['shift'] : null,
            'mode' => in_array($query['mode'] ?? null, Program::CLASS_MODES, true) ? $query['mode'] : null,
            'q' => is_string($query['q'] ?? null) ? mb_substr(trim($query['q']), 0, 100) : null,
        ];

        $programs = Program::query()->listed()->with(['department.faculty', 'level'])
            ->filter([
                'faculty' => $selected['faculty']?->id,
                'department' => $selected['department']?->id,
                'level' => $selected['level']?->id,
                'shift' => $selected['shift'],
                'mode' => $selected['mode'],
                'q' => $selected['q'],
            ])
            ->get();

        app(Seo::class)->set(['title' => __('program::program.plural'), 'path' => '/'.self::LISTING]);

        return response()->view("program::listing.{$layout}", [
            'programs' => $programs,
            'choices' => $choices,
            'selected' => $selected,
            'options' => $layouts->options('program.listing'),
        ]);
    }

    /**
     * Filter choices: visible faculties, departments and the active levels.
     *
     * @return array{faculties: Collection<int, Faculty>, departments: Collection<int, Department>, levels: Collection<int, Term>}
     */
    public function choices(string $locale): array
    {
        return [
            'faculties' => Faculty::query()->frontendVisible()->ordered()->get(),
            'departments' => Department::query()->frontendVisible()->ordered()->get(),
            'levels' => Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.level')->id)
                ->where('is_active', true)->ordered()->get(),
        ];
    }

    /** The choice whose key (short name, slug) matches the query value, case-insensitive. */
    protected function match(Collection $items, mixed $value, \Closure $key): mixed
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $items->first(fn ($item): bool => strcasecmp((string) $key($item), $value) === 0);
    }

    /**
     * FR-PROG-11: a new Program starts with its own counters — Majors and Industry Experts
     * (counted once their lists exist), Faculty Members and Approved Alumni (manual).
     */
    public static function createStatSet(Program $program): void
    {
        $key = $program->getMorphClass().':'.$program->getKey();

        if (StatSet::query()->ownedBy($key)->where('key', 'default')->exists()) {
            return;
        }

        $set = (new StatSet(['key' => 'default', 'name' => ['en' => 'Program counters', 'bn' => 'Program-এর সংখ্যা']]))->setOwnerKey($key);
        $set->save();

        foreach ([
            ['label' => ['en' => 'Majors', 'bn' => 'Major'], 'source' => 'metric', 'metric_key' => 'program.majors'],
            ['label' => ['en' => 'Faculty Members', 'bn' => 'শিক্ষক'], 'source' => 'manual'],
            ['label' => ['en' => 'Industry Experts', 'bn' => 'Industry Expert'], 'source' => 'metric', 'metric_key' => 'program.industry-experts'],
            ['label' => ['en' => 'Approved Alumni', 'bn' => 'Alumni'], 'source' => 'manual', 'rounding' => 'plus'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
