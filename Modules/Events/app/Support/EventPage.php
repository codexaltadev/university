<?php

namespace Modules\Events\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Events\Models\Event;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;
use Platform\Website\EntityTabs;

/**
 * What an event's page shows, ready for a theme (`$page` in events::show): the details beside it
 * (where it belongs, date, time, venue, category, organiser), its text made safe, the links to
 * register, join and add it to a calendar, and what other modules add (albums). Themes hold no PHP
 * logic (T-1), so everything worked out lives here.
 */
class EventPage
{
    protected array $memo = [];

    public function __construct(public Event $event, public string $base = '') {}

    public function listingUrl(): string
    {
        return url($this->base.'/'.Event::PATH);
    }

    /** The hero's title: the first 60 characters, as DIU shows it */
    public function heroTitle(): string
    {
        return Str::limit((string) $this->event->title, 60);
    }

    public function state(): string
    {
        return $this->event->state();
    }

    public function image(): ?MediaItem
    {
        return $this->event->firstMedia('featured_image');
    }

    public function body(): ?string
    {
        return filled($this->event->body) ? Str::sanitizeHtml((string) $this->event->body) : null;
    }

    /**
     * The details beside the event, those that are filled: departments and faculties it is shown in
     * (a department's faculty too), date, time, venue, category, organiser.
     *
     * @return list<array{key: string, label: string, value: string, icon: string}>
     */
    public function details(): array
    {
        $places = $this->places();
        $faculties = collect($places)->map(fn (Model $m): ?Model => str_ends_with($m::class, '\\Faculty') ? $m : ($m->faculty ?? null))->filter()->unique(fn (Model $m) => $m->getKey());
        $departments = collect($places)->filter(fn (Model $m): bool => str_ends_with($m::class, '\\Department'));
        $others = collect($places)->reject(fn (Model $m): bool => str_ends_with($m::class, '\\Department') || str_ends_with($m::class, '\\Faculty'));
        $starts = $this->event->starts_at;
        $ends = $this->event->finishesAt();
        $category = $this->event->termsIn(Event::CATEGORY)->first()?->name;

        return array_values(array_filter([
            $departments->isNotEmpty() ? ['key' => 'department', 'label' => __('events::events.page.department'), 'value' => $departments->pluck('name')->implode(', '), 'icon' => 'tag'] : null,
            ['key' => 'date', 'label' => __('events::events.page.date'), 'value' => $starts->isSameDay($ends) ? $starts->translatedFormat('F j, Y') : $starts->translatedFormat('F j, Y').' – '.$ends->translatedFormat('F j, Y'), 'icon' => 'calendar'],
            ! $this->event->is_all_day ? ['key' => 'time', 'label' => __('events::events.page.time'), 'value' => $starts->format('g:i A').($this->event->ends_at && $starts->isSameDay($ends) ? ' – '.$ends->format('g:i A') : ''), 'icon' => 'clock'] : null,
            filled($this->event->venue) ? ['key' => 'location', 'label' => __('events::events.page.location'), 'value' => (string) $this->event->venue, 'icon' => 'map-pin'] : null,
            $category ? ['key' => 'category', 'label' => __('events::events.page.category'), 'value' => (string) $category, 'icon' => 'tag'] : null,
            $faculties->isNotEmpty() ? ['key' => 'faculty', 'label' => __('events::events.page.faculty'), 'value' => $faculties->pluck('name')->implode(', '), 'icon' => 'tag'] : null,
            $others->isNotEmpty() ? ['key' => 'place', 'label' => __('events::events.page.shown_in'), 'value' => $others->pluck('name')->implode(', '), 'icon' => 'building'] : null,
            filled($this->event->organizer_label) ? ['key' => 'organizer', 'label' => __('events::events.fields.organizer'), 'value' => (string) $this->event->organizer_label, 'icon' => 'users'] : null,
        ]));
    }

    /** @return list<Model> the places the event is shown in (departments, faculties, offices …) */
    public function places(): array
    {
        return $this->memo['places'] ??= collect(app(RelationEngine::class)->targetsOf($this->event))
            ->map(function (string $key): ?Model {
                [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
                $class = Relation::getMorphedModel((string) $type) ?? $type;

                return class_exists((string) $class) ? $class::query()->find($id) : null;
            })->filter()->values()->all();
    }

    /** @return array{register: ?string, register_until: ?string, closed: bool, online: ?string, ics: ?string, google: ?string} */
    public function actions(): array
    {
        $past = $this->state() === 'past';

        return [
            'register' => $this->event->registrationOpen() ? $this->event->registration_url : null,
            'register_until' => $this->event->registrationOpen() && $this->event->registration_ends_at ? $this->event->registration_ends_at->translatedFormat('j F Y, g:i A') : null,
            'closed' => ! $this->event->registrationOpen() && filled($this->event->registration_url),
            'online' => ! $past && filled($this->event->online_url) && preg_match('#^https?://#i', (string) $this->event->online_url) ? $this->event->online_url : null,
            'ics' => $past ? null : url($this->base.'/'.Event::PATH.'/'.$this->event->slug().'.ics'),
            'google' => $past ? null : $this->event->googleCalendarUrl(),
        ];
    }

    /** @return list<array{key: string, label: string, html: string}> what other modules add (Gallery's albums …) */
    public function tabs(): array
    {
        return app(EntityTabs::class)->render($this->event);
    }
}
