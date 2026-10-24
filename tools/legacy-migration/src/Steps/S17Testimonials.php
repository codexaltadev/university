<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Program\Models\Program;
use Modules\Testimonials\Models\Testimonial;
use Modules\Testimonials\Support\Authors;
use Platform\Relations\RelationEngine;

/**
 * S17M Testimonials (DB-05 v1.5 §6, §18): DIU's `testimonials` (all on a program) → shown in that
 * program (S06) and, as DIU lists them all on /testimonials, on the main site; the person → a manual person (name, designation, organization), their title
 * "designation, organization". They are graduates now working, so type "alumni". The older
 * `mba_testimonials` repeat the MBA ones: a row with a name already migrated is skipped, any
 * other goes to the same program. Photos wait for the files (S03). Run after S06.
 */
class S17Testimonials extends Step
{
    public static function key(): string
    {
        return 'S17M';
    }

    public static function title(): string
    {
        return 'Testimonials';
    }

    public static function requires(): array
    {
        return ['testimonials', 'program'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('testimonials')) {
            $this->report->review('No testimonials table in the legacy database: nothing to migrate.');

            return;
        }

        $byName = [];   // name => program, for the MBA copies
        foreach ($this->rows('testimonials') as $row) {
            $program = $this->ids->record('department_program_type', $row->testimonialable_id, Program::class);
            $byName[mb_strtolower((string) Normalizer::text($row->name))] = $program;
            $this->migrate('testimonials', $row, $row->quote, $row->designation, $row->organization, $program, (int) $row->sort_order, true);
        }

        if (Schema::connection('legacy')->hasTable('mba_testimonials')) {
            foreach ($this->rows('mba_testimonials') as $row) {
                $name = mb_strtolower((string) Normalizer::text($row->name));
                if (array_key_exists($name, $byName)) {
                    $this->report->skipped('mba_testimonials', 'same person as in testimonials');

                    continue;
                }
                $program = $row->department_program_type_id ? $this->ids->record('department_program_type', $row->department_program_type_id, Program::class) : collect($byName)->filter()->first();
                $this->migrate('mba_testimonials', $row, $row->description, $row->batch, null, $program, 0, (bool) $row->publication_status);
            }
        }

        $this->report->review('Testimonial photos wait for the files (S03); until then the website shows them without a photo.');
    }

    protected function migrate(string $table, object $row, ?string $quote, ?string $designation, ?string $organization, mixed $program, int $order, bool $active): void
    {
        $text = Normalizer::text(trim(strip_tags(html_entity_decode((string) $quote, ENT_QUOTES | ENT_HTML5)), " \u{201C}\u{201D}\"\n\r\t"));
        if (blank($text) || blank($row->name)) {
            $this->report->skipped($table, 'no quote or no name');

            return;
        }
        if ($program === null) {
            $this->report->review("{$table} #{$row->id}: its program is not migrated (run S06 first) — shown nowhere.");
        }

        $person = $this->manualPerson($table.'@person', $row, $row->name, $designation, null, null);
        if (filled($organization)) {
            $person->update(['organization' => Normalizer::en(Normalizer::text($organization))]);
        }
        $title = implode(', ', array_filter([Normalizer::text($designation), Normalizer::text($organization)]));

        $this->upsert($table, $row, Testimonial::class, [
            'quote' => Normalizer::en($text),
            'testimonial_type' => 'alumni',
            'sort_order' => $order,
            'is_active' => $active,
        ], after: function (Testimonial $testimonial) use ($person, $title, $program, $active): void {
            app(Authors::class)->set($testimonial, $person, $title !== '' ? ['en' => $title] : null);
            app(RelationEngine::class)->sync($testimonial, $program ? [$program->getMorphClass().':'.$program->getKey()] : [], mainSite: $active, user: null);   // DIU lists every testimonial on /testimonials
        });
    }
}
