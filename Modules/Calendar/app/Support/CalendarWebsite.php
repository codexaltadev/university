<?php

namespace Modules\Calendar\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /academic-calendar (FR-CAL-04): the current calendar(s) with their dates first, then the
 * others; filters year / session / faculty / audience (AC-CAL-01). /academic-calendar/{slug}:
 * one calendar as a table, month view or timeline, with the hard copy and .ics (FR-CAL-05/06).
 * A microsite's /{entity}/academic-calendar shows its faculty's calendars (FR-CAL-09).
 */
class CalendarWebsite
{
    public const VIEWS = ['table', 'month', 'timeline'];

    public function __construct(protected CalendarFinder $finder) {}

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest === Calendar::PATH) {
            return $this->index($context, $locale);
        }
        if ($context->isMicrosite() || ! Str::startsWith($rest, Calendar::PATH.'/')) {
            return null;
        }

        $slug = Str::after($rest, Calendar::PATH.'/');
        $ics = str_ends_with($slug, '.ics');
        $slug = $ics ? substr($slug, 0, -4) : $slug;
        if (str_contains($slug, '/') || ! ($found = Calendar::findBySlug($slug, $locale))) {
            return null;
        }

        [$calendar, $current] = $found;
        if (! $calendar->isFrontendVisible()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($calendar->url($locale).($ics ? '.ics' : ''), $locale), 301);   // FR-GEN-32
        }

        $audience = $this->audienceFilter($locale);
        $events = $this->events($calendar, $audience ? [$audience->id] : []);

        if ($ics) {
            return response(Ics::build($calendar, $events, $locale), 200, [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.Str::slug($calendar->getTranslation('title', 'en', true) ?: 'calendar').'.ics"',
            ]);
        }

        $calendar->countView();
        app(Seo::class)->forRecord($calendar);

        return response()->view('calendar::show', [
            'calendar' => $calendar,
            'events' => $events,
            'view' => $this->view(),
            'months' => MonthGrid::build($events),
            'audiences' => $this->terms(CalendarEvent::AUDIENCE),
            'audience' => $audience,
            'hardCopy' => $calendar->firstMedia('hard_copy'),
        ]);
    }

    protected function index(SiteContext $context, string $locale): ?Response
    {
        $faculties = $context->isMicrosite() ? $this->finder->facultiesOf($context->entity) : [];
        if ($context->isMicrosite() && $faculties === []) {
            return null;   // an entity outside any faculty has no calendar page
        }

        $request = request();
        $facultyFilter = ! $context->isMicrosite() && $request->filled('faculty') ? $this->facultyBySlug((string) $request->query('faculty'), $locale) : null;
        $audience = $this->audienceFilter($locale);
        $query = $this->finder->calendars($facultyFilter ? [$facultyFilter->getKey()] : $faculties)->with('events');
        $all = (clone $query)->get();

        $filtered = $request->filled('year') || $request->filled('session') || $facultyFilter;
        $calendars = $all
            ->when($request->filled('year'), fn (Collection $c) => $c->where('year', (int) $request->query('year')))
            ->when($request->filled('session'), fn (Collection $c) => $c->filter(fn (Calendar $cal): bool => $cal->session_label === $request->query('session')))
            ->values();
        $current = $filtered ? $calendars : Calendar::current($calendars);

        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        app(Seo::class)->set(['title' => __('calendar::calendar.plural'), 'path' => $base.'/'.Calendar::PATH]);

        return response()->view('calendar::index', [
            'current' => $current->map(fn (Calendar $c): array => ['calendar' => $c, 'events' => $this->events($c, $audience ? [$audience->id] : [])]),
            'others' => $calendars->reject(fn (Calendar $c): bool => $current->contains($c))->values(),
            'years' => $all->pluck('year')->unique()->sortDesc()->values(),
            'sessions' => $all->pluck('session_label')->filter()->unique()->values(),
            'faculties' => $context->isMicrosite() ? collect() : $this->facultyOptions($all),
            'audiences' => $this->terms(CalendarEvent::AUDIENCE),
            'audience' => $audience,
            'faculty' => $facultyFilter,
            'filtered' => $filtered,
            'base' => $base,
        ]);
    }

    /** @return Collection<int, CalendarEvent> */
    protected function events(Calendar $calendar, array $audienceIds): Collection
    {
        return $calendar->events()->frontendVisible()->with(['type', 'terms'])
            ->when($audienceIds !== [], fn ($q) => $q->forAudience($audienceIds))->get();
    }

    /** ?view= from the visitor, else the layout chosen in Appearance › Layouts. */
    protected function view(): string
    {
        $wanted = request()->query('view');
        if (is_string($wanted) && in_array($wanted, self::VIEWS, true)) {
            return $wanted;
        }
        $layout = app(LayoutResolver::class)->for('calendar.show') ?? 'table';

        return in_array($layout, self::VIEWS, true) ? $layout : 'table';
    }

    protected function audienceFilter(string $locale): ?Term
    {
        $wanted = request()->query('audience');

        return is_string($wanted) && $wanted !== '' ? $this->terms(CalendarEvent::AUDIENCE)->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(string $vocabulary): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options($vocabulary));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }

    /** Faculties some visible calendar is for (Faculty is optional). */
    protected function facultyOptions(Collection $calendars): Collection
    {
        $class = 'Modules\\Faculty\\Models\\Faculty';
        if (! class_exists($class)) {
            return collect();
        }
        $ids = $calendars->flatMap(fn (Calendar $c): array => $c->facultyIds())->unique()->values()->all();

        return $ids === [] ? collect() : $class::query()->frontendVisible()->whereKey($ids)->ordered()->get();
    }

    protected function facultyBySlug(string $slug, string $locale): mixed
    {
        $class = 'Modules\\Faculty\\Models\\Faculty';
        if (! class_exists($class) || ! ($found = $class::findBySlug($slug, $locale))) {
            return null;
        }

        return $found[0];
    }
}
