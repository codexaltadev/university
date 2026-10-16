<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S16E Events (DB-05 v1.3 §4, §18): DIU's `events` — `event_date` + `event_time` → starts_at (no
 * time = all day), shown in their department (S04), on the main site when `is_home` (Q-26),
 * `location` → venue, `url` → registration link (checked by hand), category from
 * `event_categories` when there. Events without a title are left out; lab events wait for the
 * Lab module; `event_images` and photos for the files (S03). Run after S04.
 */
class S16Events extends Step
{
    public static function key(): string
    {
        return 'S16E';
    }

    public static function title(): string
    {
        return 'Events';
    }

    public static function requires(): array
    {
        return ['events'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('events')) {
            $this->report->review('No events table in the legacy database: nothing to migrate.');

            return;
        }

        $categories = $this->categories();
        $engine = app(RelationEngine::class);
        $counts = ['lab' => 0, 'links' => 0, 'no_place' => 0, 'photos' => 0];

        foreach ($this->rows('events', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text(trim((string) $row->title, " \t\"'"));
            if (blank($title) || blank($row->event_date)) {
                $this->report->skipped('events', blank($title) ? 'no title' : 'no date');

                continue;
            }

            $department = $row->department_id ? $this->ids->record('departments', $row->department_id, Department::class) : null;
            $counts['lab'] += $row->lab_facility_id ? 1 : 0;
            $counts['no_place'] += ! $department && (string) $row->is_home !== '1' ? 1 : 0;
            $counts['links'] += filled($row->url) ? 1 : 0;
            $counts['photos'] += filled($row->photo) ? 1 : 0;
            $allDay = blank($row->event_time);
            $starts = Carbon::parse($row->event_date.' '.($allDay ? '00:00:00' : $row->event_time));

            $this->upsert('events', $row, Event::class, [
                'title' => Normalizer::en($title),
                'body' => Normalizer::en(Normalizer::html($row->content)),
                'starts_at' => $starts,
                'is_all_day' => $allDay,
                'venue' => Normalizer::en(Normalizer::text($row->location)),
                'registration_url' => Normalizer::url($row->url),
                'active_at' => $row->created_at ?? $starts,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Event $event) use ($row, $department, $categories, $engine): void {
                $event->syncTerms(Event::CATEGORY, array_filter([$categories[$row->event_category_id] ?? null]));
                $engine->sync($event, $department ? [$department->getMorphClass().':'.$department->getKey()] : [], mainSite: (string) $row->is_home === '1', user: null);
            });
        }

        $this->report->review("{$counts['links']} events had a link: it came in as the registration link — check the ones that are something else.");
        $this->report->review("{$counts['photos']} event pictures and the photos of `event_images` wait for the files (S03).");
        if ($counts['lab'] > 0) {
            $this->report->review("{$counts['lab']} lab events wait for the Lab module (shown where else they belong until then).");
        }
        if ($counts['no_place'] > 0) {
            $this->report->review("{$counts['no_place']} events have no department and were not on DIU's homepage: shown nowhere — give them a place or delete them.");
        }
    }

    /** @return array<int, int> legacy event_category id => term id */
    protected function categories(): array
    {
        if (! Schema::connection('legacy')->hasTable('event_categories')) {
            return [];
        }
        $vocabulary = app(Vocabularies::class)->find(Event::CATEGORY)->id;
        $map = [];
        foreach ($this->rows('event_categories') as $row) {
            $name = Normalizer::text($row->title ?? $row->name ?? $row->category_name ?? null);
            if (blank($name)) {
                continue;
            }
            Vocabularies::ensureTerms(Event::CATEGORY, [['name' => ['en' => $name], 'slug' => $row->slug ?? null]]);
            $map[$row->id] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        return array_filter($map);
    }
}
