<?php

namespace Modules\Tuition\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Models\TuitionLine;

/**
 * What /tuition-fees shows, ready for a theme (`$page` in tuition::listing.*), as DIU's /tuition-fees and /int-tuition-fees:
 * the fee categories to switch, the levels to pick, a card per faculty with a row per tuition — majors, credits, duration,
 * payable at admission, the semester fee or, where the fees go by year (international), each year — and the totals; texts
 * and links from Settings › Tuition Fees. Themes hold no PHP logic (T-1).
 */
class TuitionPage
{
    /** More majors than this show as "Total N majors are available" (DIU) */
    public const MAJORS_LISTED = 6;

    protected array $memo = [];

    public function __construct(protected array $data) {}

    public function title(): string
    {
        return (string) (settings('tuition.title') ?: __('tuition::tuition.tuition_fees'));
    }

    public function text(): ?string
    {
        return filled($t = settings('tuition.text')) ? (string) $t : null;
    }

    public function image(): ?string
    {
        return media_url(settings('tuition.image'), 'large');
    }

    public function notice(): ?string
    {
        return filled($n = settings('tuition.notice')) ? (string) $n : null;
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list((array) settings('tuition.quick_links', []));
    }

    /** @return list<array{label: string, url: string}> */
    public function helpLinks(): array
    {
        return link_list((array) settings('tuition.help_links', []));
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(): array
    {
        return [
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => __('tuition::tuition.tuition_fees'), 'url' => url('/'.TuitionWebsite::PAGE)],
        ];
    }

    /** "Local Tuition Fee (BDT)" */
    public function heading(): ?string
    {
        $category = $this->category();

        return $category ? $category->name.' ('.$category->currency.')' : null;
    }

    /** @return list<array{label: string, url: string, current: bool}> the fee categories (Local, International) */
    public function categories(): array
    {
        $current = $this->category();

        return collect($this->data['categories'] ?? [])->map(fn (FeeCategory $c): array => [
            'label' => (string) $c->name,
            'url' => url('/'.TuitionWebsite::PAGE).'?category='.urlencode((string) $c->code),
            'current' => $current?->is($c) ?? false,
        ])->values()->all();
    }

    /** @return list<array{key: string, label: string}> "All Programs" and the levels the tuitions have (Undergraduate Program …) */
    public function levels(): array
    {
        $levels = $this->tuitions()->map(fn (Tuition $t) => $t->program?->level)->filter()->unique('id')->sortBy('sort_order');

        return [
            ['key' => 'all', 'label' => __('tuition::tuition.page.all_programs')],
            ...$levels->map(fn ($level): array => ['key' => 'level-'.$level->id, 'label' => __('tuition::tuition.page.level_program', ['level' => (string) $level->name])])->values()->all(),
        ];
    }

    /** @return array{majors: bool, term: bool, years: list<string>, other: bool} which columns the category's tables need */
    public function columns(): array
    {
        return $this->memo['columns'] ??= (function (): array {
            $tuitions = $this->tuitions();
            $years = $tuitions->flatMap(fn (Tuition $t) => $t->linesOf(TuitionLine::YEAR)->map(fn (TuitionLine $l): string => (string) $l->label))->unique()->values()->all();

            return [
                'majors' => $years === [] && $tuitions->contains(fn (Tuition $t): bool => $this->majors($t) !== null),   // DIU: not with the yearly (international) fees
                'term' => $years === [] && $tuitions->contains(fn (Tuition $t): bool => $t->termFee() !== null),
                'years' => $years,
                'other' => $years !== [] && $tuitions->contains(fn (Tuition $t): bool => $t->otherFees() !== null),
            ];
        })();
    }

    /**
     * @return list<array{name: string, key: string, filters: list<array{0: string, 1: string}>, rows: list<array{name: string, url: ?string, filter: array{0: string, 1: string}, majors: ?string,
     *     credits: ?string, duration: ?string, admission: ?string, term: ?string, years: list<?string>, other: ?string, tuition: ?string, total: ?string}>}>
     */
    public function groups(): array
    {
        $years = $this->columns()['years'];

        return collect($this->data['groups'] ?? [])->map(fn (array $g): array => [
            'name' => (string) $g['faculty']->name,
            'key' => 'faculty-'.$g['faculty']->id,
            'filters' => $g['tuitions']->map(fn (Tuition $t): array => $this->filter($t, $g['faculty']))->values()->all(),
            'rows' => $g['tuitions']->map(function (Tuition $t) use ($years, $g): array {
                $money = fn ($v): ?string => $v === null ? null : $t->category->money($v);
                $range = fn ($min, $max): ?string => $min === null ? null : $money($min).($max !== null && (float) $max > (float) $min ? ' – '.$money($max) : '');
                $name = (string) $t->program->name.(filled($t->label) ? ' ('.$t->label.')' : '');
                $yearLines = $t->linesOf(TuitionLine::YEAR);

                return [
                    'name' => $name,
                    'url' => $t->program->url() ? url($t->program->url()) : null,
                    'filter' => $this->filter($t, $g['faculty']),
                    'majors' => $this->majors($t),
                    'credits' => $t->creditsText(),
                    'duration' => filled($t->duration_label) ? (string) $t->duration_label : null,
                    'admission' => $money($t->admissionFee()),
                    'term' => $money($t->termFee()),
                    'years' => array_map(fn (string $label): ?string => $money($yearLines->first(fn (TuitionLine $l): bool => (string) $l->label === $label)?->amount), $years),
                    'other' => $money($t->otherFees()),
                    'tuition' => $range($t->tuition_min, $t->tuition_max),
                    'total' => $range($t->total_min, $t->total_max),
                ];
            })->values()->all(),
        ])->values()->all();
    }

    /** What the page's level tabs and search match: [level key, words in lower case] */
    protected function filter(Tuition $t, $faculty): array
    {
        $name = (string) $t->program->name.(filled($t->label) ? ' ('.$t->label.')' : '');

        return ['level-'.$t->program->level_term_id, Str::lower(implode(' ', array_filter([$name, $t->program->short_name, $t->program->department?->short_name, $faculty->short_name])))];
    }

    /** The program's majors: their names, or "Total N majors are available" when there are many */
    protected function majors(Tuition $t): ?string
    {
        $majors = $t->program?->relationLoaded('majors') ? $t->program->majors : collect();
        if ($majors->isEmpty()) {
            return null;
        }

        return $majors->count() > self::MAJORS_LISTED
            ? __('tuition::tuition.page.majors_total', ['count' => $majors->count()])
            : $majors->map(fn ($m): string => (string) $m->title)->implode(', ');
    }

    protected function category(): ?FeeCategory
    {
        return $this->data['category'] ?? null;
    }

    /** @return Collection<int, Tuition> */
    protected function tuitions(): Collection
    {
        return $this->memo['tuitions'] ??= collect($this->data['groups'] ?? [])->flatMap(fn (array $g) => $g['tuitions']);
    }
}
