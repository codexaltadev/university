<?php

namespace Modules\Gallery\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Gallery\Models\Album;
use Platform\Context\SiteContext;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * What the gallery shows, ready for a theme (`$page` in gallery::index and gallery::show), as DIU's
 * gallery does: "Browse by Category" — each category with its albums — beside the pictures of the
 * chosen category or album, 20 a page, with a lightbox. The pictures are the Media Library's items
 * the albums use (no copies). Themes hold no PHP logic (T-1).
 */
class GalleryPage
{
    public const PER_PAGE = 20;

    protected array $memo = [];

    public function __construct(protected array $data, protected SiteContext $context) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Album::PATH);
    }

    public function album(): ?Album
    {
        return $this->data['album'] ?? null;
    }

    public function category(): ?Term
    {
        return $this->data['category'] ?? null;
    }

    /** The heading over the pictures: the album's or the category's name */
    public function heading(): ?string
    {
        return $this->album()?->title ?? $this->category()?->name;
    }

    /**
     * The categories that have albums here, each with them (newest first); albums without a
     * category last. The chosen one is open.
     *
     * @return list<array{name: string, url: ?string, current: bool, open: bool, albums: list<array{title: string, url: string, current: bool}>}>
     */
    public function tree(): array
    {
        return $this->memo['tree'] ??= (function (): array {
            $albums = GalleryWebsite::shown($this->context)->with('terms')->get();
            $order = array_keys(app(Vocabularies::class)->options(Album::CATEGORY));
            $terms = Term::query()->whereKey($order)->get()->keyBy('id');
            $topOf = fn (?Term $t): ?Term => $t && $t->parent_id && $terms->has($t->parent_id) ? $terms[$t->parent_id] : $t;
            $current = $this->album();
            $link = fn (Album $a): array => ['title' => (string) $a->title, 'url' => url(($this->data['base'] ?? '').'/'.Album::PATH.'/'.$a->slug()), 'current' => (bool) $current?->is($a)];

            $groups = [];
            $other = [];
            foreach ($albums as $album) {
                $top = $topOf($album->termsIn(Album::CATEGORY)->first());
                if ($top === null) {
                    $other[] = $album;

                    continue;
                }
                $groups[$top->id] ??= ['term' => $top, 'albums' => []];
                $groups[$top->id]['albums'][] = $album;
            }
            uasort($groups, fn (array $a, array $b): int => array_search($a['term']->id, $order, true) <=> array_search($b['term']->id, $order, true));

            $tree = array_values(array_map(function (array $g) use ($link): array {
                $albums = array_map($link, $g['albums']);
                $chosen = (bool) $this->category()?->is($g['term']);

                return ['name' => (string) $g['term']->name, 'url' => $this->listingUrl().'?category='.$g['term']->slug(), 'current' => $chosen,
                    'open' => $chosen || collect($albums)->contains('current', true), 'albums' => $albums];
            }, $groups));
            if ($other !== []) {
                $albums = array_map($link, $other);
                $tree[] = ['name' => __('gallery::gallery.page.other'), 'url' => null, 'current' => false, 'open' => collect($albums)->contains('current', true), 'albums' => $albums];
            }

            return $tree;
        })();
    }

    /**
     * The pictures of the chosen album or category, a page of them; null when neither is chosen.
     *
     * @return LengthAwarePaginator<int, array{thumb: string, src: string, caption: string, album: string}>|null
     */
    public function photos(): ?LengthAwarePaginator
    {
        if (array_key_exists('photos_page', $this->memo)) {
            return $this->memo['photos_page'];
        }
        if ($album = $this->album()) {
            $entries = $this->entries(collect([$album]));
        } elseif ($category = $this->category()) {
            $ids = [$category->id, ...$category->descendantIds()];
            $entries = $this->entries(GalleryWebsite::shown($this->context)->whereHas('terms', fn ($t) => $t->whereIn('core_terms.id', $ids))->get());
        } else {
            return $this->memo['photos_page'] = null;
        }
        $page = max(1, (int) request('page', 1));

        return $this->memo['photos_page'] = (new LengthAwarePaginator($entries->forPage($page, self::PER_PAGE)->values(), $entries->count(), self::PER_PAGE, $page, [
            'path' => request()->url(), 'query' => request()->query(),
        ]));
    }

    /** An album's date and series ("Lecture series"), when it has them */
    public function meta(): ?string
    {
        $album = $this->album();

        return $album ? (implode(' · ', array_filter([
            $album->occurred_on?->translatedFormat('j F Y'),
            $album->series_type ? __("gallery::gallery.series.{$album->series_type}") : null,
        ])) ?: null) : null;
    }

    /** "Showing 1 - 20 of 54 photos" */
    public function showing(): ?string
    {
        $photos = $this->photos();
        if ($photos === null || $photos->total() === 0) {
            return null;
        }

        return __('gallery::gallery.page.showing', ['from' => $photos->firstItem(), 'to' => $photos->lastItem(), 'total' => $photos->total()]);
    }

    /** @return list<array{title: string, url: string, cover: ?string, photos: int, date: ?string}> the album cards when nothing is chosen */
    public function cards(): array
    {
        return collect(($this->data['albums'] ?? null)?->items() ?? [])->map(fn (Album $a): array => [
            'title' => (string) $a->title,
            'url' => url(($this->data['base'] ?? '').'/'.Album::PATH.'/'.$a->slug()),
            'cover' => $a->coverUrl('medium'),
            'photos' => $a->photoCount(),
            'date' => $a->occurred_on?->translatedFormat('j F Y'),
        ])->all();
    }

    /** @return Collection<int, array{thumb: string, src: string, caption: string, album: string}> */
    protected function entries(Collection $albums): Collection
    {
        return $albums->flatMap(fn (Album $a) => $a->entries('image')->map(fn (array $e): array => [
            'thumb' => (string) ($e['item']->url('medium') ?? $e['item']->url()),
            'src' => (string) ($e['item']->url('large') ?? $e['item']->url()),
            'caption' => (string) ($e['caption'] ?? ''),
            'album' => (string) $a->title,
        ]))->values();
    }
}
