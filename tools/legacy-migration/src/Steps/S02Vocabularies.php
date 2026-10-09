<?php

namespace Legacy\Migration\Steps;

use Legacy\Migration\Support\Normalizer;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S02 — vocabularies and terms (DB-03 §13, §15.1). So far the ones of the active
 * academic modules: program.major-category from program_major_categories, and
 * program.tag from the program_types sub-types. Levels are not migrated: the four
 * DIU levels are seeded by the Program module; S06 maps each program to one.
 */
class S02Vocabularies extends Step
{
    /** program_types rows that are levels or unused (§15.1), not tags. */
    public const NOT_TAGS = [1 => 'level: Undergraduate', 2 => 'level: Graduate', 3 => 'level: Diploma', 26 => 'level: Post Graduate Diploma', 12 => 'Proposed Programs: not migrated'];

    public static function key(): string
    {
        return 'S02';
    }

    public static function title(): string
    {
        return 'Vocabularies and terms (program major categories, program tags)';
    }

    public static function requires(): array
    {
        return ['program'];
    }

    protected function handle(): void
    {
        $vocabularies = app(Vocabularies::class);

        $categories = $vocabularies->find('program.major-category');
        foreach ($this->rows('program_major_categories') as $row) {
            $this->upsert('program_major_categories', $row, Term::class, [
                'vocabulary_id' => $categories->id,
                'name' => Normalizer::en(Normalizer::text($row->title)),
                'description' => Normalizer::en(Normalizer::text($row->description)),
            ], existing: $this->same(Term::class, ['vocabulary_id' => $categories->id, 'name->en' => Normalizer::text($row->title)]));
        }

        $tags = $vocabularies->find('program.tag');
        foreach ($this->rows('program_types') as $row) {
            if (isset(self::NOT_TAGS[$row->id]) || $row->deleted_at !== null) {
                $this->report->skipped('program_types', $row->deleted_at !== null ? 'deleted' : self::NOT_TAGS[$row->id]);

                continue;
            }

            $this->upsert('program_types', $row, Term::class, [
                'vocabulary_id' => $tags->id,
                'name' => Normalizer::en(Normalizer::text($row->program_name)),
                'is_active' => (bool) $row->publication_status,
            ], existing: $this->same(Term::class, ['vocabulary_id' => $tags->id, 'name->en' => Normalizer::text($row->program_name)]));
        }
    }
}
