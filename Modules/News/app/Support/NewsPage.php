<?php

namespace Modules\News\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\News\Models\Post;
use Platform\Taxonomy\Vocabularies;

/**
 * What the news pages show, ready for a theme (`$page` in news::index and news::show), as DIU News
 * does: "Latest" with the two newest on the first page, cards with their category and date, the
 * categories to filter by, and a post's picture, details, text and three more. Themes hold no PHP
 * logic (T-1).
 */
class NewsPage
{
    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Post::PATH);
    }

    /** The listing's address with some filters changed (null = removed); the page starts over */
    public function url(array $change = []): string
    {
        $query = array_filter(array_merge([
            'category' => ($this->data['category'] ?? null)?->slug(),
            'tag' => ($this->data['tag'] ?? null)?->slug(),
            'q' => trim((string) request('q')) ?: null,
            'year' => request('year'), 'month' => request('month'),
        ], $change), fn ($v): bool => $v !== null && $v !== '');

        return $this->listingUrl().($query ? '?'.http_build_query($query) : '');
    }

    /** The first page without filters opens with "Latest" and its two newest posts */
    public function showsLatest(): bool
    {
        $posts = $this->data['posts'] ?? null;

        return $posts && $posts->currentPage() === 1 && ! request()->hasAny(['category', 'tag', 'q', 'year']) && $posts->count() > 0;
    }

    /** @return list<array{title: string, url: string, image: ?string, category: ?string, date: string, text: ?string}> the two newest ("Latest") */
    public function latest(): array
    {
        return $this->showsLatest() ? array_slice($this->cards(), 0, 2) : [];
    }

    /** @return list<array{title: string, url: string, image: ?string, category: ?string, date: string, text: ?string}> the grid (after "Latest") */
    public function grid(): array
    {
        return $this->showsLatest() ? array_slice($this->cards(), 2) : $this->cards();
    }

    /** @return list<array{name: string, url: string, current: bool}> */
    public function categories(): array
    {
        return collect($this->data['categories'] ?? [])->map(fn ($t): array => [
            'name' => (string) $t->name, 'url' => $this->url(['category' => $t->slug()]), 'current' => (bool) ($this->data['category'] ?? null)?->is($t),
        ])->all();
    }

    /** @return list<array{label: string, url: string, total: int, current: bool}> months with posts, newest first (FR-NEWS-05) */
    public function archive(): array
    {
        return collect($this->data['archive'] ?? [])->map(fn ($row): array => [
            'label' => Carbon::create((int) $row->year, (int) $row->month)->translatedFormat('F Y'),
            'url' => $this->url(['year' => $row->year, 'month' => $row->month, 'category' => null, 'tag' => null, 'q' => null]),
            'total' => (int) $row->total,
            'current' => (int) request('year') === (int) $row->year && (int) request('month') === (int) $row->month,
        ])->all();
    }

    /** The heading beside the list: the category or tag chosen, else Settings' "All News" */
    public function title(): string
    {
        return ($this->data['category'] ?? null)?->name
            ?? (($tag = $this->data['tag'] ?? null) ? '#'.$tag->name : (settings('news.all_title') ?: __('news::news.page.all')));
    }

    // ── A post ──────────────────────────────────────────────────────────

    public function post(): ?Post
    {
        return $this->data['post'] ?? null;
    }

    public function image(): ?string
    {
        return $this->post()?->featuredImage()?->url('large');
    }

    public function category(): ?string
    {
        return $this->post()?->termsIn(Post::CATEGORY)->first()?->name;
    }

    public function date(): string
    {
        return (string) ($this->post()?->active_at ?? $this->post()?->created_at)?->translatedFormat('M j, Y');
    }

    public function views(): int
    {
        return (int) $this->post()?->viewTotal();
    }

    public function author(): ?string
    {
        return settings('news.show_author', true) ? $this->post()?->authorName() : null;
    }

    public function body(): ?string
    {
        return filled($this->post()?->body) ? Str::sanitizeHtml((string) $this->post()->body) : null;
    }

    /** @return list<array{name: string, url: string}> */
    public function tags(): array
    {
        return $this->post()?->termsIn(Vocabularies::SHARED_TAGS)->map(fn ($t): array => ['name' => (string) $t->name, 'url' => $this->listingUrl().'?tag='.$t->slug()])->values()->all() ?? [];
    }

    /** @return list<array{title: string, url: string, image: ?string, category: ?string, date: string, text: ?string}> three more from the same place */
    public function related(): array
    {
        return array_slice(array_map(fn (Post $p): array => $this->card($p), ($this->data['related'] ?? collect())->all()), 0, 3);
    }

    /** @return list<array{title: string, url: string, image: ?string, category: ?string, date: string, text: ?string}> */
    protected function cards(): array
    {
        return array_map(fn (Post $p): array => $this->card($p), ($this->data['posts'] ?? null)?->items() ?? []);
    }

    /** @return array{title: string, url: string, image: ?string, category: ?string, date: string, text: ?string} */
    protected function card(Post $p): array
    {
        return [
            'title' => (string) $p->title,
            'url' => url(($this->data['base'] ?? '').'/'.Post::PATH.'/'.$p->slug()),
            'image' => $p->featuredImage()?->url('medium'),
            'category' => $p->termsIn(Post::CATEGORY)->first()?->name,
            'date' => (string) ($p->active_at ?? $p->created_at)?->translatedFormat('M j, Y'),
            'text' => Str::limit(trim(html_entity_decode(strip_tags((string) ($p->excerpt ?: $p->subtitle ?: $p->body)))), 140) ?: null,
        ];
    }
}
