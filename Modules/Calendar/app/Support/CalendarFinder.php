<?php

namespace Modules\Calendar\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Which calendars and dates a place shows (DB-03 §12 v1.6): the faculty of a microsite or
 * program comes from the entity hierarchy, so a Department or Program shows its Faculty's
 * calendars (and those for every faculty); a program's audience is the audience term named
 * like its level (Undergraduate, Graduate).
 */
class CalendarFinder
{
    /**
     * Faculty ids above (or at) an entity; empty for the main site or an entity outside any faculty.
     *
     * @return list<int>
     */
    public function facultiesOf(?Model $entity): array
    {
        if ($entity === null) {
            return [];
        }
        $key = EntityHierarchy::key($entity->getMorphClass(), $entity->getKey());

        return collect([$key, ...app(EntityHierarchy::class)->ancestors($key)])
            ->filter(fn (string $k): bool => str_starts_with($k, Calendar::FACULTY.':'))
            ->map(fn (string $k): int => (int) Str::after($k, ':'))
            ->values()->all();
    }

    /** Visible calendars, newest first; for faculties = theirs and the ones for every faculty. */
    public function calendars(array $facultyIds = []): Builder
    {
        return Calendar::query()->frontendVisible()
            ->when($facultyIds !== [], fn (Builder $q) => $q->forFaculty($facultyIds))
            ->newestFirst();
    }

    /**
     * Upcoming dated dates of the home-page calendars (FR-CAL-07).
     *
     * @return Collection<int, CalendarEvent>
     */
    public function upcoming(int $limit = 5, array $facultyIds = [], array $audienceIds = []): Collection
    {
        $calendars = $this->calendars($facultyIds)->where('show_on_home', true)->pluck('id');

        return CalendarEvent::query()->frontendVisible()->whereIn('calendar_id', $calendars)
            ->upcoming()->when($audienceIds !== [], fn (Builder $q) => $q->forAudience($audienceIds))
            ->with(['calendar', 'type'])->limit($limit)->get();
    }

    /**
     * The audience term matching a program's level by name, if any.
     *
     * @return list<int>
     */
    public function audienceOfProgram(Model $program): array
    {
        $level = $program->getAttribute('level_term_id') ? Term::query()->find($program->getAttribute('level_term_id')) : null;
        if (! $level) {
            return [];
        }
        $name = Str::lower((string) $level->getTranslation('name', 'en', false));

        return Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(CalendarEvent::AUDIENCE)->id)->get()
            ->filter(fn (Term $t): bool => Str::lower((string) $t->getTranslation('name', 'en', false)) === $name)
            ->pluck('id')->values()->all();
    }
}
