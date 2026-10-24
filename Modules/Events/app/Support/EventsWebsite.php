<?php

namespace Modules\Events\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Events\Models\Event;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /events and /{entity}/events (FR-EVT-03): the place's events — tab Upcoming (with ongoing, by
 * start) or Past (newest first, AC-EVT-01) — as a list, cards, timeline or month calendar
 * (?month=2026-12); ?category=, ?q= (title, summary), ?faculty=fsit (main site: shown in that
 * faculty or below it). /events/{slug} (and under a microsite): only where the event is shown;
 * .ics download (FR-EVT-07).
 */
class EventsWebsite
{
    public const VIEWS = ['list', 'cards', 'timeline', 'calendar'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Event::PATH && ! Str::startsWith($rest, Event::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Event::PATH) {
            return $this->listing($context, $base, $locale);
        }

        $slug = Str::after($rest, Event::PATH.'/');
        $ics = str_ends_with($slug, '.ics');
        $slug = $ics ? substr($slug, 0, -4) : $slug;
        if (str_contains($slug, '/') || ! ($found = Event::findBySlug($slug, $locale))) {
            return null;
        }
        [$event, $current] = $found;
        if (! $this->shown($context)->whereKey($event->getKey())->exists()) {
            return null;   // not shown in this place, inactive or not yet shown
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Event::PATH.'/'.$event->slug($locale).($ics ? '.ics' : ''), $locale), 301);
        }
        if ($ics) {
            return response(EventIcs::build($event, $locale), 200, [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.(Str::slug((string) $event->getTranslation('title', 'en', true)) ?: 'event').'.ics"',
            ]);
        }

        $event->countView();
        app(Seo::class)->forRecord($event);

        return response()->view('events::show', [
            'event' => $event,
            'speakers' => $event->people(Event::ROLES),
            'gallery' => $event->mediaIn('gallery'),
            'base' => $base,
        ]);
    }

    /** Visible events of this place: the main site's, or an entity's and those below it. */
    protected function shown(SiteContext $context): Builder
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Event::class)->includeDescendants($context->isMicrosite())->query()->reorder();
    }

    protected function listing(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        if ($context->isMicrosite() && ! $this->shown($context)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('events::events.plural'), 'path' => $base.'/'.Event::PATH]);

        $categories = $this->terms();
        $wanted = $request->query('category');
        $category = is_string($wanted) && $wanted !== '' ? $categories->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        $q = trim((string) $request->query('q'));
        $faculty = $context->isMicrosite() ? null : static::faculty($request->query('faculty'));
        $filtered = fn (): Builder => $this->shown($context)
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w   // JSON text compares case-sensitively: lower both
                ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, ?))) LIKE ?', ['$."'.$locale.'"', '%'.mb_strtolower($q).'%'])
                ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(summary, ?))) LIKE ?', ['$."'.$locale.'"', '%'.mb_strtolower($q).'%'])))
            ->when($faculty, fn (Builder $query) => $query->whereIn('events_events.id', app(RelationEngine::class)->for($faculty->getMorphClass().':'.$faculty->getKey())
                ->content(Event::class)->includeDescendants(true)->query()->reorder()->select('events_events.id')));

        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('events.listing') ?? 'list');
        $layout = in_array($layout, self::VIEWS, true) ? $layout : 'list';
        $tab = $request->query('tab') === 'past' ? 'past' : 'upcoming';

        $data = ['categories' => $categories->whereNull('parent_id')->values(), 'category' => $category, 'q' => $q, 'faculty' => $faculty, 'layout' => $layout, 'tab' => $tab, 'base' => $base];

        if ($layout === 'calendar') {
            $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? Carbon::createFromFormat('!Y-m', (string) $request->query('month')) : now()->startOfMonth();
            $events = $filtered()->where('starts_at', '<=', $month->copy()->endOfMonth())
                ->where(fn (Builder $q) => $q->where('starts_at', '>=', $month)->orWhere('ends_at', '>=', $month))
                ->orderBy('starts_at')->get();

            return response()->view('events::index', $data + ['events' => $events, 'month' => $month, 'weeks' => $this->weeks($month, $events)]);
        }

        $events = ($tab === 'past' ? $filtered()->past() : $filtered()->upcoming())->paginate(12)->withQueryString();

        return response()->view('events::index', $data + ['events' => $events, 'month' => null, 'weeks' => []]);
    }

    /**
     * The month as weeks of seven days (Saturday first), each day with its events.
     *
     * @param  Collection<int, Event>  $events
     * @return list<list<array{day: Carbon, inMonth: bool, events: list<Event>}>>
     */
    protected function weeks(Carbon $month, Collection $events): array
    {
        $start = $month->copy()->startOfWeek(Carbon::SATURDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY);
        $weeks = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $on = $day->copy();
            $weeks[intdiv((int) $start->diffInDays($on), 7)][] = [
                'day' => $on,
                'inMonth' => $on->isSameMonth($month),
                'events' => $events->filter(fn (Event $e): bool => $on->betweenIncluded($e->starts_at->copy()->startOfDay(), $e->finishesAt()->copy()->endOfDay()))->values()->all(),
            ];
        }

        return array_values($weeks);
    }

    /** ?faculty=fsit: the Faculty by its short name (Faculty module optional) */
    public static function faculty(mixed $wanted): ?Model
    {
        $class = 'Modules\\Faculty\\Models\\Faculty';
        if (! is_string($wanted) || $wanted === '' || ! class_exists($class)) {
            return null;
        }

        return $class::query()->frontendVisible()->whereRaw('LOWER(short_name) = ?', [mb_strtolower($wanted)])->first();
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Event::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
