<?php

namespace Modules\Committee\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Committee\Models\Committee;
use Platform\Assignments\PersonView;

/**
 * What the committee pages show, ready for a theme (`$page` in committee::index and committee::show),
 * as DIU's /committee/{slug}: every running committee of this place on the left with its number of
 * members (one chosen), and the chosen committee — on /committees the first, as DIU opens its first —
 * with a row per member: number, initials or photo, name, a note, the committee position and the
 * designation; its term, text, formation order and earlier terms. Themes hold no PHP logic (T-1).
 */
class CommitteesPage
{
    protected ?Collection $members = null;

    public function __construct(protected array $data) {}

    public function base(): string
    {
        return (string) ($this->data['base'] ?? '');
    }

    public function listingUrl(): string
    {
        return url($this->base().'/'.Committee::PATH);
    }

    public function url(Committee $committee): string
    {
        return url($this->base().'/'.Committee::PATH.'/'.$committee->slug());
    }

    /** The committee shown: this one, or on /committees the first running one (DIU opens its first) */
    public function current(): ?Committee
    {
        if (($committee = $this->data['committee'] ?? null) instanceof Committee) {
            return $committee;
        }

        return $this->all()->first();
    }

    /**
     * The running committees of this place, by category when there are categories.
     *
     * @return list<array{category: ?string, items: list<array{name: string, url: string, count: int, current: bool}>}>
     */
    public function sidebar(): array
    {
        $current = $this->current();

        return $this->all()->groupBy(fn (Committee $c): string => (string) $c->category?->name)
            ->map(fn (Collection $committees, string $category): array => [
                'category' => $category !== '' ? $category : null,
                'items' => $committees->map(fn (Committee $c): array => [
                    'name' => (string) $c->name,
                    'url' => $this->url($c),
                    'count' => $this->count($c),
                    'current' => $current !== null && $current->is($c),
                ])->values()->all(),
            ])->values()->all();
    }

    public function total(): int
    {
        return $this->all()->count();
    }

    /**
     * @return list<array{serial: int, name: string, initials: string, photo: ?string, url: ?string, note: ?string, position: string, designation: ?string, email: ?string, phone: ?string}>
     */
    public function members(): array
    {
        return $this->people()->values()->map(fn (PersonView $p, int $i): array => [
            'serial' => $i + 1,
            'name' => $p->name(),
            'initials' => $this->initials($p->name()),
            'photo' => $p->photoUrl(),
            'url' => $p->profileUrl(),
            'note' => $p->note(),
            'position' => $p->roleLabel,
            'designation' => $p->designation(),
            'email' => $p->email(),
            'phone' => $p->phone(),
        ])->all();
    }

    public function description(): ?string
    {
        $committee = $this->current();

        return $committee && filled($committee->description) ? Str::sanitizeHtml((string) $committee->description) : null;
    }

    public function orderPdfUrl(): ?string
    {
        $pdf = array_key_exists('orderPdf', $this->data) ? $this->data['orderPdf'] : $this->current()?->firstMedia('order_pdf');

        return $pdf?->url();
    }

    /** @return list<array{label: string, url: string}> earlier terms, newest first */
    public function previousTerms(): array
    {
        $previous = $this->data['previous'] ?? $this->current()?->previousTerms() ?? collect();

        return collect($previous)->map(fn (Committee $t): array => ['label' => (string) ($t->termLabel() ?? $t->name), 'url' => $this->url($t)])->all();
    }

    public function nextTermUrl(): ?string
    {
        return ($next = $this->data['next'] ?? null) instanceof Committee ? $this->url($next) : null;
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url($this->base() ?: '/')],
            ['label' => __('committee::committee.plural'), 'url' => $this->listingUrl()],
            ($c = $this->current()) ? ['label' => (string) $c->name, 'url' => $this->url($c)] : null,
        ]));
    }

    /** @return Collection<int, Committee> */
    protected function all(): Collection
    {
        if (array_key_exists('committees', $this->data)) {
            return collect($this->data['committees']);
        }

        return collect($this->data['groups'] ?? [])->flatMap(fn (array $g) => $g['committees'])->values();
    }

    protected function people(): Collection
    {
        if ($this->members !== null) {
            return $this->members;
        }

        return $this->members = array_key_exists('members', $this->data) ? collect($this->data['members']) : collect($this->current()?->people() ?? []);
    }

    protected function count(Committee $committee): int
    {
        return ($current = $this->current()) && $current->is($committee) ? $this->people()->count() : collect($committee->people())->count();
    }

    protected function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) preg_replace('/^((dr|prof|professor|mr|mrs|ms|engr|md)\.?\s+)+/i', '', $name))) ?: []));

        return $words === [] ? '?' : Str::upper(Str::substr($words[0], 0, 1).(count($words) > 1 ? Str::substr(end($words), 0, 1) : ''));
    }
}
