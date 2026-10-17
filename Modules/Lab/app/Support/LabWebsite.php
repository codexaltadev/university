<?php

namespace Modules\Lab\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * Labs on the website (FR-LAB-05/06/10): /labs (and a department's /fsit/cse/labs) by type,
 * department and search, as grid / list / cards; on a lab's microsite, /equipment, /scopes,
 * /projects, /products and a project's or product's own page. The microsite home comes from the
 * kernel (home: lab::microsite.home).
 */
class LabWebsite
{
    public const VIEWS = ['grid', 'list', 'cards'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite() && $context->entity instanceof Lab) {
            return $this->inLab($context->entity, $rest, $locale);
        }
        if ($rest !== Lab::LISTING) {
            return null;
        }
        $department = $context->isMicrosite() && class_exists(Lab::DEPARTMENT) && $context->entity instanceof (Lab::DEPARTMENT) ? $context->entity : null;
        if ($context->isMicrosite() && $department === null) {
            return null;
        }

        return $this->listing($department);
    }

    protected function listing(?object $department): Response
    {
        $labs = Lab::query()->listed()->with('terms')->when($department, fn ($q) => $q->where('department_id', $department->getKey()))->get();
        $types = $this->types($labs);
        $wanted = request()->query('type');
        $type = is_string($wanted) ? $types->first(fn (Term $t): bool => $t->slug() === $wanted) : null;
        $departments = $department || ! class_exists(Lab::DEPARTMENT) ? collect()
            : (Lab::DEPARTMENT)::query()->whereKey($labs->pluck('department_id')->filter()->unique())->get();
        $dept = request()->integer('department') ?: null;
        $q = trim((string) request()->query('q'));
        $shown = $labs
            ->when($type, fn (Collection $c) => $c->filter(fn (Lab $l): bool => $l->termsIn(Lab::TYPE)->contains('id', $type->id)))
            ->when($dept, fn (Collection $c) => $c->where('department_id', $dept))
            ->when($q !== '', fn (Collection $c) => $c->filter(fn (Lab $l): bool => str_contains(mb_strtolower($l->name.' '.$l->short_name), mb_strtolower($q))))
            ->values();
        $view = request()->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('lab.listing') ?? 'grid');
        app(Seo::class)->set(['title' => __('lab::lab.plural'), 'path' => '/'.Lab::LISTING]);

        return response()->view('lab::listing', [
            'labs' => $shown, 'types' => $types, 'type' => $type, 'departments' => $departments, 'department' => $dept, 'q' => $q,
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
        ]);
    }

    protected function inLab(Lab $lab, string $rest, string $locale): Response|RedirectResponse|null
    {
        $segments = explode('/', $rest);
        $type = array_search($segments[0] ?? '', LabItem::SEGMENTS, true);
        if ($type === false || count($segments) > 2) {
            return null;
        }
        $items = $lab->items()->ofType($type)->where('is_active', true)->with('terms')->get();
        if (! isset($segments[1])) {
            if ($items->isEmpty()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('lab::lab.types_plural.'.$type).' — '.$lab->name, 'path' => '/'.$rest]);

            return response()->view('lab::items', ['lab' => $lab, 'type' => $type, 'groups' => self::byCategory($items, $type)]);
        }
        if (! in_array($type, LabItem::WITH_PAGE, true) || ! ($found = LabItem::findBySlug($segments[1], $locale))) {
            return null;
        }
        [$item, $current] = $found;
        if ($item->lab_id !== $lab->id || $item->item_type !== $type || ! $item->is_active) {
            return null;
        }
        if (! $current) {
            return redirect((string) $item->url($locale), 301);
        }
        app(Seo::class)->forRecord($item);

        return response()->view('lab::item', ['lab' => $lab, 'item' => $item]);
    }

    /** @return Collection<int, Term> the lab types in the vocabulary's order that some lab has */
    protected function types(Collection $labs): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Lab::TYPE));
        $used = $labs->flatMap(fn (Lab $l) => $l->termsIn(Lab::TYPE)->pluck('id'))->unique();

        return Term::query()->whereKey($used->all())->get()->sortBy(fn (Term $t): int => (int) array_search($t->id, $order, true))->values();
    }

    /** @return list<array{term: ?Term, items: Collection<int, LabItem>}> by category in the vocabulary's order, uncategorised last */
    public static function byCategory(Collection $items, string $type): array
    {
        $vocabulary = LabItem::vocabulary($type);
        $order = array_keys(app(Vocabularies::class)->options($vocabulary));
        $groups = $items->groupBy(fn (LabItem $i): int => (int) $i->termsIn($vocabulary)->first()?->id);

        return $groups->sortBy(fn ($g, int $id): int => $id === 0 ? PHP_INT_MAX : (int) array_search($id, $order, true))
            ->map(fn (Collection $g, int $id): array => ['term' => $id ? $g->first()->termsIn($vocabulary)->first() : null, 'items' => $g->values()])
            ->values()->all();
    }

    /** FR-LAB-11: a new lab starts with its own counters — equipment, projects, products (automatic). */
    public static function createStatSet(Lab $lab): void
    {
        $key = $lab->getMorphClass().':'.$lab->getKey();
        if (StatSet::query()->ownedBy($key)->where('key', 'default')->exists()) {
            return;
        }
        $set = (new StatSet(['key' => 'default', 'name' => ['en' => 'Lab counters', 'bn' => 'ল্যাবের সংখ্যা']]))->setOwnerKey($key);
        $set->save();
        foreach ([
            ['label' => ['en' => 'Equipment', 'bn' => 'যন্ত্রপাতি'], 'source' => 'metric', 'metric_key' => 'lab.equipment'],
            ['label' => ['en' => 'Projects', 'bn' => 'প্রজেক্ট'], 'source' => 'metric', 'metric_key' => 'lab.projects'],
            ['label' => ['en' => 'Products', 'bn' => 'প্রোডাক্ট'], 'source' => 'metric', 'metric_key' => 'lab.products'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
