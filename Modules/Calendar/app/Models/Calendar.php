<?php

namespace Modules\Calendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Calendar\Database\Factories\CalendarFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasViewCount;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Models\BaseModel;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * An academic calendar (M-11, DB-03 §12 v1.6): one session ("Spring 2026") for some faculties
 * — none = every faculty — with its dates, a hard copy (PDF or picture) and a view count.
 * Shown on the website when active; "current" comes from its dates, not from a column.
 */
class Calendar extends BaseModel
{
    /** @use HasFactory<CalendarFactory> */
    use HasFactory;

    use HasMediaUsage;      // hard_copy
    use HasSeo;
    use HasSlug;            // /academic-calendar/{slug}
    use HasSortOrder;
    use HasTranslations;
    use HasViewCount;       // P-17
    use IsAttachable;       // the faculties it is for (core_relations)
    use IsSearchable;

    public const PATH = 'academic-calendar';

    public const FACULTY = 'faculty.faculty';

    public static string $titleAttribute = 'title';

    protected $table = 'calendar_calendars';

    protected $fillable = ['title', 'session_label', 'year', 'duration_label', 'show_on_home', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'duration_label'];

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return ['year' => 'integer', 'show_on_home' => 'boolean'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class)->ordered();
    }

    /** Newest first: year, then the manual order. */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc($this->qualifyColumn('year'))->orderBy($this->qualifyColumn('sort_order'))->orderByDesc($this->getQualifiedKeyName());
    }

    /**
     * Calendars for a faculty: those linked to it and those linked to no faculty.
     *
     * @param  int|list<int>  $facultyIds
     */
    public function scopeForFaculty(Builder $query, int|array $facultyIds): Builder
    {
        $keys = array_map(fn (int $id): string => self::FACULTY.':'.$id, (array) $facultyIds);

        return $query->where(fn (Builder $q) => $q->shownIn($keys)->orWhereDoesntHave('contentRelations', fn (Builder $r) => $r->where('target_type', self::FACULTY)));
    }

    /** @return list<int> */
    public function facultyIds(): array
    {
        return collect($this->targetKeys())
            ->filter(fn (string $key): bool => str_starts_with($key, self::FACULTY.':'))
            ->map(fn (string $key): int => (int) substr($key, strlen(self::FACULTY) + 1))
            ->values()->all();
    }

    /** @param list<int> $ids */
    public function syncFaculties(array $ids): void
    {
        app(RelationEngine::class)->sync($this, array_map(fn ($id): string => self::FACULTY.':'.(int) $id, array_values(array_unique($ids))));
    }

    /** The first and last dated day of its events, or null without any. */
    public function span(): ?array
    {
        $events = $this->relationLoaded('events') ? $this->events : $this->events()->get();
        $starts = $events->pluck('starts_on')->filter();
        if ($starts->isEmpty()) {
            return null;
        }
        $ends = $events->map(fn (CalendarEvent $e): ?Carbon => $e->ends_on ?? $e->starts_on)->filter();

        return [$starts->min(), $ends->max()];
    }

    /** Today lies within its dates. */
    public function isCurrent(?Carbon $today = null): bool
    {
        $span = $this->span();
        $today ??= Carbon::today();

        return $span !== null && $today->betweenIncluded($span[0], $span[1]);
    }

    /**
     * The calendars running today; none running = the newest ones of the latest year.
     *
     * @param  Collection<int, Calendar>  $calendars  visible ones, with events
     * @return Collection<int, Calendar>
     */
    public static function current(Collection $calendars): Collection
    {
        $running = $calendars->filter(fn (Calendar $c): bool => $c->isCurrent())->values();
        if ($running->isNotEmpty() || $calendars->isEmpty()) {
            return $running;
        }

        // Nothing running: the next one to start, else the latest year's
        $today = Carbon::today();
        $upcoming = $calendars->filter(fn (Calendar $c): bool => ($s = $c->span()) !== null && $s[0]->gt($today))
            ->sortBy(fn (Calendar $c) => $c->span()[0])->values();
        if ($upcoming->isNotEmpty()) {
            $first = $upcoming->first()->span()[0];

            return $upcoming->filter(fn (Calendar $c): bool => $c->span()[0]->isSameMonth($first))->values();
        }

        $year = $calendars->max('year');

        return $calendars->where('year', $year)->values();
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    protected function searchBodyAttributes(): array
    {
        return ['duration_label'];
    }

    /** FR-SEO-08: an EventSeries of the dated events. */
    protected function jsonLd(string $locale): array
    {
        $span = $this->span();

        return array_filter([
            '@type' => 'EventSeries',
            'name' => $this->getTranslation('title', $locale, true),
            'startDate' => $span ? $span[0]->toDateString() : null,
            'endDate' => $span ? $span[1]->toDateString() : null,
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): CalendarFactory
    {
        return CalendarFactory::new();
    }
}
