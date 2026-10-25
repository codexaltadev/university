<?php

namespace Modules\Facilities\Support;

use Illuminate\Support\Str;
use Modules\Facilities\Models\Facility;
use Platform\Taxonomy\Term;

/**
 * What the facilities pages show, ready for a theme (`$page` in facilities::index and facilities::show):
 * the categories to pick and a picture card per facility as DIU's program page shows them (picture,
 * title, two lines, its page or its outside address); a facility's page with its pictures. Title,
 * line and picture from Settings › Facilities. Themes hold no PHP logic (T-1).
 */
class FacilitiesPage
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return (string) (settings('facilities.title') ?: __('facilities::facilities.plural'));
    }

    public function text(): ?string
    {
        return filled($text = settings('facilities.text')) ? (string) $text : null;
    }

    public function image(): ?string
    {
        return media_url(settings('facilities.image'), 'large');
    }

    public function listingUrl(array $query = []): string
    {
        return url(($this->data['base'] ?? '').'/'.Facility::PATH).($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')],
            ['label' => $this->title(), 'url' => $this->listingUrl()],
            $last !== null ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** @return list<array{label: string, url: string, current: bool}> "All" and the categories, when there are some */
    public function categories(): array
    {
        $terms = collect($this->data['categories'] ?? []);
        if ($terms->isEmpty()) {
            return [];
        }
        $current = $this->data['category'] ?? null;

        return [
            ['label' => __('facilities::facilities.all'), 'url' => $this->listingUrl(), 'current' => $current === null],
            ...$terms->map(fn (Term $t): array => ['label' => (string) $t->name, 'url' => $this->listingUrl(['category' => $t->slug()]), 'current' => $current !== null && $current->is($t)])->all(),
        ];
    }

    /** @return list<array{title: string, url: ?string, external: bool, image: ?string, text: ?string}> */
    public function cards(): array
    {
        return collect($this->data['facilities'] ?? [])->map(fn (Facility $f): array => [
            'title' => (string) $f->title,
            'url' => $f->href($this->data['base'] ?? null),
            'external' => ! $f->hasPage() && (bool) $f->open_in_new_tab,
            'image' => $f->firstMedia('image')?->url('medium') ?? $f->firstMedia('image')?->url(),
            'text' => ($t = trim(html_entity_decode(strip_tags((string) ($f->summary ?: $f->body))))) !== '' ? Str::limit($t, 160) : null,
        ])->values()->all();
    }

    /** One facility's page */
    public function facility(): array
    {
        /** @var Facility $f */
        $f = $this->data['facility'];

        return [
            'title' => (string) $f->title,
            'image' => $f->firstMedia('image')?->url('large') ?? $f->firstMedia('image')?->url(),
            'summary' => filled($f->summary) ? (string) $f->summary : null,
            'body' => Str::sanitizeHtml((string) $f->body),
            'external' => filled($f->external_url) ? (str_starts_with((string) $f->external_url, '/') ? url($f->external_url) : (string) $f->external_url) : null,
            'gallery' => collect($this->data['gallery'] ?? [])->map(fn ($item): array => ['url' => $item->url('large') ?? $item->url(), 'thumb' => $item->url('medium') ?? $item->url(), 'alt' => (string) ($item->alt_text ?: $f->title)])->all(),
        ];
    }
}
