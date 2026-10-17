<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Facilities\Models\Facility;
use Modules\Program\Models\Program;
use Platform\Relations\RelationEngine;

/**
 * S18F Facilities (DB-06 v1.3 §4): DIU's `facilities` — without an owner → the main site, on a
 * program (DepartmentProgramType) → shown in that program (S06); `is_home` → highlighted there; the
 * link and its target kept; the text only when it says more than the title. Icons are font names,
 * not pictures: they wait for S03 / a picture by hand.
 */
class S18Facilities extends Step
{
    public static function key(): string
    {
        return 'S18F';
    }

    public static function title(): string
    {
        return 'Facilities';
    }

    public static function requires(): array
    {
        return ['facilities'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('facilities')) {
            $this->report->review('No facilities table in the legacy database: nothing to migrate.');

            return;
        }
        $engine = app(RelationEngine::class);
        $rows = $this->rows('facilities', fn ($q) => $q->whereNull('deleted_at'));
        $placeOf = function (object $row): ?string {
            $program = $row->facilityable_id && str_contains((string) $row->facilityable_type, 'DepartmentProgramType') && class_exists(Program::class)
                ? $this->ids->record('department_program_type', $row->facilityable_id, Program::class) : null;
            if ($row->facilityable_id && $program === null) {
                $this->report->review("facilities #{$row->id}: its program is not migrated (run S06 first) — shown nowhere.");
            }

            return $program ? $program->getMorphClass().':'.$program->getKey() : null;
        };
        // A program row without its own title "uses" a main facility (reference_id): that facility is shown in the program too
        $uses = [];
        foreach ($rows->filter(fn ($r) => Normalizer::text($r->facility_title) === null && (int) $r->reference_id) as $row) {
            if ($place = $placeOf($row)) {
                $uses[(int) $row->reference_id][] = $place;
            }
            $this->report->skipped('facilities', 'uses facility #'.$row->reference_id.' — shown in its program too');
        }

        foreach ($rows as $row) {
            $title = Normalizer::text($row->facility_title);
            if ($title === null) {
                if (! (int) $row->reference_id) {
                    $this->report->skipped('facilities', 'no title');
                }

                continue;
            }
            $place = $placeOf($row);
            $places = array_values(array_unique(array_filter([$place, ...($uses[$row->id] ?? [])])));
            $link = Normalizer::url($row->facility_link);
            $body = Normalizer::html($row->facility_content);

            $this->upsert('facilities', $row, Facility::class, [
                'title' => Normalizer::en($title),
                'body' => Normalizer::en($body),
                'external_url' => $link,
                'open_in_new_tab' => $row->target === '_blank',
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: function (Facility $facility, bool $created) use ($engine, $place, $places, $row): void {
                $this->keepSlug($facility, $row->facility_slug, $created);
                $highlight = $row->is_home ? [($place ?? 'site:0') => ['order' => max(0, (int) $row->serial)]] : [];
                $engine->sync($facility, $places, mainSite: $place === null, highlights: $highlight, user: null);
            }, existing: $this->same(Facility::class, ['title->en' => $title]));
        }
        $this->report->review('Facility icons were font icons ("security", "reading" …): give each a picture in the Media Library (after S03).');
    }
}
