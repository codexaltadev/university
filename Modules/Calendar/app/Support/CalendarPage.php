<?php

namespace Modules\Calendar\Support;

use Illuminate\Support\Collection;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;

/**
 * What the academic calendar pages show, ready for a theme (`$page` in calendar::index and
 * calendar::show), as DIU's Registrar office shows it: a card per calendar — the session to switch
 * to, its year, duration and how many dates, then its dates with their type. Themes hold no PHP
 * logic (T-1).
 */
class CalendarPage
{
    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url('/'.Calendar::PATH);
    }

    /**
     * The calendars to show as cards: the running ones (index) or this one (show).
     *
     * @return list<array{title: string, url: string, running: bool, year: ?string, duration: ?string, total: int, rows: list<array{title: string, type: ?string, color: ?string, date: string}>}>
     */
    public function cards(): array
    {
        if (($calendar = $this->data['calendar'] ?? null) instanceof Calendar) {
            return [$this->card($calendar, $this->data['events'] ?? collect())];
        }

        return collect($this->data['current'] ?? [])->map(fn (array $item): array => $this->card($item['calendar'], collect($item['events'])))->values()->all();
    }

    /** @return list<array{title: string, url: string, current: bool}> every calendar to switch to (the session dropdown) */
    public function sessions(): array
    {
        $shown = collect($this->cards())->pluck('url')->all();
        $others = collect($this->data['others'] ?? []);
        $current = collect($this->data['current'] ?? [])->pluck('calendar');
        $calendar = $this->data['calendar'] ?? null;

        return $current->concat($others)->when($calendar, fn (Collection $c) => $c->prepend($calendar))
            ->unique(fn (Calendar $c) => $c->getKey())
            ->map(fn (Calendar $c): array => ['title' => (string) $c->title, 'url' => url($c->url()), 'current' => in_array(url($c->url()), $shown, true)])
            ->values()->all();
    }

    /** @param  Collection<int, CalendarEvent>  $events */
    protected function card(Calendar $calendar, Collection $events): array
    {
        return [
            'title' => (string) $calendar->title,
            'url' => url($calendar->url()),
            'running' => $calendar->isCurrent(),
            'year' => $calendar->year ? (string) $calendar->year : null,
            'duration' => filled($calendar->duration_label) ? (string) $calendar->duration_label : null,
            'total' => $events->count(),
            'rows' => $events->map(fn (CalendarEvent $e): array => [
                'title' => (string) $e->title,
                'type' => $e->type?->name,
                'color' => $e->type ? $e->color('var(--color-muted, #6b7280)') : null,
                'date' => $e->when(),
            ])->values()->all(),
        ];
    }
}
