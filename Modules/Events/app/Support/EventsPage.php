<?php

namespace Modules\Events\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Events\Models\Event;

/**
 * What the events listing shows, ready for a theme (`$page` in events::index): the cards, the
 * faculties to filter by, and the addresses of each filter. Themes hold no PHP logic (T-1).
 */
class EventsPage
{
    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Event::PATH);
    }

    /** This listing's address with some filters changed (null = removed); the page starts over */
    public function url(array $change = []): string
    {
        $query = array_filter(array_merge([
            'tab' => ($this->data['tab'] ?? null) === 'past' ? 'past' : null,
            'view' => request('view'),
            'category' => $this->data['category']?->slug(),
            'q' => ($this->data['q'] ?? '') ?: null,
            'faculty' => $this->data['faculty'] ? mb_strtolower((string) $this->data['faculty']->short_name) : null,
        ], $change), fn ($v): bool => $v !== null && $v !== '');

        return $this->listingUrl().($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{slug: string, name: string, current: bool}> faculties to filter by (main site only) */
    public function faculties(): array
    {
        $class = 'Modules\\Faculty\\Models\\Faculty';
        if (filled($this->data['base'] ?? '') || ! class_exists($class)) {
            return [];
        }

        return $class::query()->listed()->get()
            ->filter(fn (Model $f): bool => filled($f->short_name))
            ->map(fn (Model $f): array => ['slug' => mb_strtolower((string) $f->short_name), 'name' => (string) $f->name, 'current' => (bool) $this->data['faculty']?->is($f)])
            ->values()->all();
    }

    public function isCalendar(): bool
    {
        return ($this->data['layout'] ?? null) === 'calendar';
    }

    /**
     * @return list<array{title: string, url: string, image: ?string, date: string, text: ?string, state: string}>
     */
    public function cards(): array
    {
        if ($this->isCalendar()) {
            return [];
        }

        return collect($this->data['events']->items())->map(fn (Event $e): array => [
            'title' => (string) $e->title,
            'url' => url(($this->data['base'] ?? '').'/'.Event::PATH.'/'.$e->slug()),
            'image' => $e->firstMedia('featured_image')?->url('medium'),
            'date' => $e->starts_at->format('Y-m-d'),
            'text' => Str::limit(trim(html_entity_decode(strip_tags((string) ($e->summary ?: $e->body)))), 160) ?: null,
            'state' => $e->state(),
        ])->all();
    }

    /** The calendar's cells' event links */
    public function eventUrl(Event $event): string
    {
        return url(($this->data['base'] ?? '').'/'.Event::PATH.'/'.$event->slug());
    }
}
