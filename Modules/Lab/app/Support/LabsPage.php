<?php

namespace Modules\Lab\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Lab\Models\Lab;

/**
 * What the labs listings show, ready for a theme (`$page` in lab::listing): on /labs the
 * departments that have labs, by faculty (DIU's Laboratory Facilities); on a department's
 * /fsit/cse/labs its labs and the first one's page. Themes hold no PHP logic (T-1).
 */
class LabsPage
{
    public function __construct(public Collection $labs, public ?Model $place = null) {}

    /** A department's labs page, else /labs */
    public static function departmentUrl(?Model $department): string
    {
        $base = $department && method_exists($department, 'url') ? rtrim((string) $department->url(), '/') : '';

        return url($base.'/'.Lab::LISTING);
    }

    /** The hero line of a department's labs page (Settings › Labs, :department = its name) */
    public static function departmentHero(?Model $department): string
    {
        return $department ? str_replace(':department', (string) $department->name, (string) settings('lab.department_hero')) : (string) settings('lab.department_text');
    }

    public function heroText(): string
    {
        return $this->place ? static::departmentHero($this->place) : (string) settings('lab.listing_text');
    }

    /**
     * Faculties in their order, each with its departments that have labs (a card each); labs
     * without a department last, as their own cards.
     *
     * @return list<array{name: ?string, cards: list<array{name: string, text: ?string, image: ?string, url: string, count: int}>}>
     */
    public function groups(): array
    {
        $departments = class_exists(Lab::DEPARTMENT)
            ? (Lab::DEPARTMENT)::query()->whereKey($this->labs->pluck('department_id')->filter()->unique())->with('faculty')->get()
            : collect();
        $counts = $this->labs->countBy('department_id');
        $card = fn (Model $d): array => [
            'name' => (string) $d->name,
            'text' => filled($d->about) ? Str::limit(trim(html_entity_decode(strip_tags((string) $d->about))), 220) : null,
            'image' => ($d->firstMedia('cover') ?? $d->firstMedia('photo'))?->url('medium'),
            'url' => static::departmentUrl($d),
            'count' => (int) ($counts[$d->getKey()] ?? 0),
        ];

        $groups = $departments->groupBy(fn (Model $d): int => (int) $d->faculty_id)
            ->sortBy(fn (Collection $ds): string => sprintf('%05d-%s', (int) $ds->first()->faculty?->sort_order, $ds->first()->faculty?->name))
            ->map(fn (Collection $ds): array => [
                'name' => $ds->first()->faculty?->name,
                'cards' => $ds->sortBy(fn (Model $d): string => sprintf('%05d-%s', (int) $d->sort_order, $d->name))->map($card)->values()->all(),
            ])->values();

        $alone = $this->labs->whereNull('department_id');
        if ($alone->isNotEmpty()) {
            $groups->push(['name' => __('lab::lab.other'), 'cards' => $alone->map(fn (Lab $l): array => [
                'name' => (string) $l->name,
                'text' => filled($l->summary) ? Str::limit((string) $l->summary, 220) : null,
                'image' => $l->firstMedia('cover')?->url('medium'),
                'url' => (string) $l->url(),
                'count' => 0,
            ])->values()->all()]);
        }

        return $groups->all();
    }

    /** On a department's labs page: the first lab's page */
    public function first(): ?LabPage
    {
        return ($lab = $this->labs->first()) ? new LabPage($lab) : null;
    }
}
