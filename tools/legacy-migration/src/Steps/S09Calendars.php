<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Calendar\Database\Seeders\CalendarDatabaseSeeder;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Support\DateText;
use Modules\Faculty\Models\Faculty;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Views\ViewCounter;

/**
 * S09 Academic Calendar (DB-03 v1.6 §12, §15): the calendars (not deleted) with their faculty
 * group from the title — "(FBE,FHSS,FHLS,FSIT)", "(FE)", "(TRI)" = FBE, FHSS, FHLS, FSIT,
 * "(BI)" = FE (owner, 2026-10-16) — view counts, homepage flag, and their dates: written dates
 * read into real dates where they can be (the rest stays words, Data Migration Plan 7.6), a type
 * guessed from the title, the audience from `calendar_event_calender_program`. Run after S04.
 */
class S09Calendars extends Step
{
    /** @var array<string, list<string>> legacy group => faculty short names */
    public const GROUPS = [
        'TRI' => ['FBE', 'FHSS', 'FHLS', 'FSIT'],
        'BI' => ['FE'],
    ];

    /** @var array<string, string> legacy short name => short name of the migrated faculty (S04 kept DIU's "ENGINEERING") */
    public const ALIASES = ['FE' => 'ENGINEERING'];

    /** @var array<string, string> title word => event-type slug, first match wins */
    public const TYPE_WORDS = [
        'result' => 'result', 'grade' => 'result',
        'registration' => 'registration', 'advising' => 'registration', 'orientation' => 'registration', 'admission' => 'registration',
        'exam' => 'exam', 'midterm' => 'exam', 'mid-term' => 'exam', 'ct-' => 'exam', 'quiz' => 'exam', 'preparatory' => 'exam',
        'vacation' => 'holiday', 'holiday' => 'holiday', 'break' => 'holiday', 'eid' => 'holiday', 'puja' => 'holiday',
        'class' => 'class',
    ];

    public static function key(): string
    {
        return 'S09';
    }

    public static function title(): string
    {
        return 'Academic Calendar: calendars and dates';
    }

    public static function requires(): array
    {
        return ['calendar', 'faculty'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('calenders')) {
            $this->report->review('No calenders table in the legacy database: nothing to migrate.');

            return;
        }

        $faculties = Faculty::query()->withoutGlobalScopes()->whereNull('deleted_at')->get()
            ->mapWithKeys(fn (Faculty $f): array => [Str::upper((string) $f->short_name) => $f->id]);
        $views = app(ViewCounter::class);
        $calendars = [];

        foreach ($this->rows('calenders', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text($row->title);
            [$session, $group] = $this->split($title);
            $ids = $this->facultyIds($group, $faculties->all());

            $calendars[$row->id] = $this->upsert('calenders', $row, Calendar::class, [
                'title' => Normalizer::en($title),
                'session_label' => $session,
                'year' => (int) $row->year,
                'duration_label' => Normalizer::en(Normalizer::text($row->duration)),
                'show_on_home' => (string) $row->is_home === '1',
                'is_active' => (string) $row->is_active === '1',
            ], after: function (Calendar $calendar, bool $created) use ($row, $ids, $views): void {
                $this->keepSlug($calendar, Str::slug((string) $row->slug), $created);
                $calendar->syncFaculties($ids);
                $views->seed($calendar, (int) $row->total_views);
            });

            if ($group !== null && $ids === []) {
                $this->report->review("Calendar \"{$title}\": faculty group \"{$group}\" matched no faculty — shown for every faculty.");
            }
            if (filled($row->hard_copy)) {
                $this->report->review("Calendar \"{$title}\": hard copy {$row->hard_copy} waits for the files (S03).");
            }
        }

        $types = $this->typeIds();
        $audiences = $this->audienceIds();
        $pivot = $this->legacy()->table('calendar_event_calender_program')->get()->groupBy('calendar_event_id');
        $parsed = $wordsOnly = 0;

        foreach ($this->rows('calendar_events', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $calendar = $calendars[$row->calendar_id] ?? null;
            if ($calendar === null) {
                $this->report->skipped('calendar_events', 'calendar deleted or missing');

                continue;
            }

            $words = Normalizer::text($row->date_explanation) ?? '';
            $words = preg_match('/[\pL\pN]/u', $words) ? $words : 'To be announced';   // "------"
            $date = DateText::parse($words, $calendar->year);
            $date ? $parsed++ : $wordsOnly++;
            $title = Normalizer::text($row->title);

            $this->upsert('calendar_events', $row, CalendarEvent::class, [
                'calendar_id' => $calendar->id,
                'title' => Normalizer::en($title),
                'starts_on' => $date['starts_on'] ?? null,
                'ends_on' => $date['ends_on'] ?? null,
                'date_label' => Normalizer::en($date ? ($date['rest'] ?: null) : $words),
                'type_term_id' => $types[$this->guessType((string) $title)] ?? null,
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (string) $row->is_active === '1',
            ], after: function (CalendarEvent $event) use ($pivot, $row, $audiences): void {
                $ids = collect($pivot->get($row->id, []))->map(fn ($p) => $audiences[$p->calender_program_id] ?? null)->filter()->unique()->values()->all();
                // Both audiences = for everyone (no term), as on the legacy site
                $event->syncTerms(CalendarEvent::AUDIENCE, count($ids) === count($audiences) ? [] : $ids);
            });
        }

        $this->report->review("{$parsed} dates read as real dates; {$wordsOnly} kept as words only (Data Migration Plan 7.6: the Registrar fills these for the running session).");
    }

    /**
     * "Spring-2026 (TRI)" → ["Spring 2026", "TRI"]; "Fall 2022" → ["Fall 2022", null].
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function split(string $title): array
    {
        $group = preg_match('/\(([^)]*)\)\s*$/', $title, $m) ? Str::upper(trim($m[1])) : null;
        $session = trim(preg_replace('/\([^)]*\)\s*$/', '', $title));
        $session = preg_replace('/^(spring|summer|fall)\s*-\s*(\d{4})$/i', '$1 $2', $session);

        return [Str::limit($session, 50, '') ?: null, $group];
    }

    /**
     * @param  array<string, int>  $faculties  short name => id
     * @return list<int>
     */
    protected function facultyIds(?string $group, array $faculties): array
    {
        if ($group === null) {
            return [];
        }
        $names = self::GROUPS[$group] ?? array_map('trim', explode(',', $group));

        return array_values(array_filter(array_map(
            fn (string $n): ?int => $faculties[Str::upper($n)] ?? $faculties[self::ALIASES[Str::upper($n)] ?? ''] ?? null,
            $names,
        )));
    }

    protected function guessType(string $title): string
    {
        $title = Str::lower($title);
        foreach (self::TYPE_WORDS as $word => $type) {
            if (str_contains($title, $word)) {
                return $type;
            }
        }

        return 'other';
    }

    /** @return array<string, int> event-type key (CalendarDatabaseSeeder::TYPES) => term id, matched by English name */
    protected function typeIds(): array
    {
        $terms = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(CalendarEvent::TYPE)->id)->get();

        return collect(CalendarDatabaseSeeder::TYPES)
            ->map(fn (array $t): ?int => $terms->first(fn (Term $term): bool => Str::lower((string) $term->getTranslation('name', 'en', false)) === Str::lower($t[0]))?->id)
            ->filter()->all();
    }

    /** @return array<int, int> legacy calender_programs id => audience term id (by name; a missing one is made) */
    protected function audienceIds(): array
    {
        $vocabulary = app(Vocabularies::class)->find(CalendarEvent::AUDIENCE)->id;
        $map = [];
        foreach ($this->rows('calender_programs', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $name = Normalizer::text($row->name);
            Vocabularies::ensureTerms(CalendarEvent::AUDIENCE, [['name' => ['en' => $name], 'slug' => $row->slug]]);
            $map[$row->id] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => Str::lower((string) $t->getTranslation('name', 'en', false)) === Str::lower((string) $name))?->id;
        }

        return array_filter($map);
    }
}
