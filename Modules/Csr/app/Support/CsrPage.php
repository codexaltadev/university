<?php

namespace Modules\Csr\Support;

use Illuminate\Support\Str;
use Modules\Csr\Models\Activity;
use Platform\Settings\Settings;
use Platform\Taxonomy\Term;

/**
 * What the CSR pages show, ready for a theme (`$page` in csr::index and csr::show), as DIU's
 * /csr-activities: the featured activity with its picture and the impact numbers, "Our Approach"
 * with the next highlighted ones, "Where We Create Impact" — a search, the categories to pick and
 * their cards (picture, line) — and "Stories of Change", the activities searched and picked; the
 * CSR Report. Texts, numbers and the report from Settings › Social Responsibility. Themes hold no PHP logic (T-1).
 */
class CsrPage
{
    public function __construct(protected array $data) {}

    public function text(string $key): ?string
    {
        return filled($value = settings('csr.'.$key)) ? (string) $value : null;
    }

    public function title(): string
    {
        return ($category = $this->data['category'] ?? null) ? (string) $category->name : ($this->text('title') ?? __('csr::csr.plural'));
    }

    public function subtitle(): ?string
    {
        return ($category = $this->data['category'] ?? null) ? ($category->description ?: null) : $this->text('subtitle');
    }

    public function image(): ?string
    {
        return media_url(settings('csr.image'), 'large');
    }

    public function listingUrl(array $query = []): string
    {
        return url(($this->data['base'] ?? '').'/'.Activity::PATH).($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')],
            ['label' => $this->text('title') ?? __('csr::csr.plural'), 'url' => $this->listingUrl()],
            ($c = $this->data['category'] ?? null) ? ['label' => (string) $c->name, 'url' => request()->url()] : null,
            $last !== null ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** The top of /csr (not a category page, nothing searched or picked): the featured activity and "Our Approach" */
    public function isHome(): bool
    {
        return ($this->data['category'] ?? null) === null && ($this->data['picked'] ?? null) === null && $this->search() === ''
            && ! array_key_exists('activity', $this->data);
    }

    /** @return array{title: string, url: string, image: ?string}|null the first highlighted (else the newest) */
    public function featured(): ?array
    {
        $first = collect($this->data['featured'] ?? [])->first();

        return $first ? $this->card($first) : null;
    }

    /** @return list<array<string, mixed>> the next highlighted ones (DIU's "is_home" two) */
    public function approach(): array
    {
        return collect($this->data['featured'] ?? [])->slice(1)->map(fn (Activity $a): array => $this->card($a))->values()->all();
    }

    /** @return list<array{value: string, label: string}> the impact numbers */
    public function stats(): array
    {
        return collect((array) settings('csr.stats'))->map(fn ($s): ?array => is_array($s) && filled($s['value'] ?? null)
            ? ['value' => (string) $s['value'], 'label' => (string) (app(Settings::class)->translate($s['label'] ?? null) ?? '')]
            : null)->filter()->values()->all();
    }

    /** @return array{label: string, url: string}|null */
    public function report(): ?array
    {
        return ($url = media_url(settings('csr.report'))) ? ['label' => $this->text('report_label') ?? 'CSR Report', 'url' => $url] : null;
    }

    /**
     * The focus areas: on a category page its sub categories; on /csr the categories that have a picture or a line
     * (DIU's focus areas are sub categories), else the top ones — each with picture and line.
     *
     * @return list<array{name: string, text: ?string, image: ?string, url: string, current: bool}>
     */
    public function areas(): array
    {
        $all = collect($this->data['categories'] ?? []);
        $category = $this->data['category'] ?? null;
        $picked = $this->data['picked'] ?? null;
        $categories = app(Categories::class);

        $areas = $category ? $all->where('parent_id', $category->id) : $all->filter(fn (Term $t): bool => filled($t->description) || $categories->image($t) !== null);
        if (! $category && $areas->isEmpty()) {
            $areas = $all->whereNull('parent_id');
        }

        return $areas->map(fn (Term $t): array => [
            'name' => (string) $t->name,
            'text' => $t->description ?: null,
            'image' => $categories->image($t)?->url('medium'),
            'url' => $this->listingUrl(array_filter(['category' => $t->slug(), 'q' => $this->search() ?: null])).'#csr-stories',
            'current' => $picked !== null && $picked->is($t),
        ])->values()->all();
    }

    /** @return array<string, string> slug => name, every category (the select) */
    public function categoryOptions(): array
    {
        return collect($this->data['categories'] ?? [])->mapWithKeys(fn (Term $t): array => [(string) $t->slug() => (string) $t->name])->all();
    }

    public function pickedSlug(): string
    {
        return (string) (($this->data['picked'] ?? null)?->slug() ?? '');
    }

    public function search(): string
    {
        return (string) ($this->data['q'] ?? '');
    }

    /** @return list<array<string, mixed>> */
    public function stories(): array
    {
        return collect(($this->data['stories'] ?? null)?->items() ?? [])->map(fn (Activity $a): array => $this->card($a))->values()->all();
    }

    /** @return array{title: string, url: string, image: ?string, date: ?string, category: ?string, summary: ?string} */
    public function card(Activity $activity): array
    {
        return [
            'title' => (string) $activity->title,
            'url' => url(($this->data['base'] ?? '').'/'.Activity::PATH.'/'.$activity->slug()),
            'image' => $activity->firstMedia('image')?->url('large'),
            'date' => $activity->occurred_on?->translatedFormat('j M Y'),
            'category' => $activity->termsIn(Activity::CATEGORY)->first()?->name,
            'summary' => filled($activity->summary) ? (string) $activity->summary : null,
        ];
    }

    /** One activity's page */
    public function activity(): array
    {
        /** @var Activity $activity */
        $activity = $this->data['activity'];

        return $this->card($activity) + [
            'body' => Str::sanitizeHtml((string) $activity->body),
            'video' => $activity->videoEmbed(),
            'video_url' => $activity->video_url ?: null,
            'external' => $activity->external_url ?: null,
            'gallery' => collect($this->data['gallery'] ?? [])->map(fn ($item): array => ['url' => $item->url('large') ?? $item->url(), 'thumb' => $item->url('medium') ?? $item->url(), 'alt' => (string) ($item->alt_text ?: $activity->title)])->all(),
        ];
    }
}
