<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Scholarship\Models\Scholarship;

/**
 * S07 (scholarship part, DB-03 §11.3 v1.4): DIU's scholarship "categories" are scholarships
 * themselves (DIU Scholarship, Korean Government, Erasmus …, each a full page) and its
 * `scholarships` rows are the calls / opportunities under one of them (all under Erasmus).
 * Old addresses are kept; unpublished calls come over inactive.
 */
class S07Scholarships extends Step
{
    public static function key(): string
    {
        return 'S07S';
    }

    public static function title(): string
    {
        return 'Scholarships and their calls';
    }

    public static function requires(): array
    {
        return ['scholarship'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('scholarship_categories')) {
            $this->report->review('No scholarship tables in the legacy database: nothing to migrate.');

            return;
        }

        foreach ($this->rows('scholarship_categories') as $row) {
            $title = Normalizer::text($row->title);

            $this->upsert('scholarship_categories', $row, Scholarship::class, [
                'parent_id' => null,
                'title' => Normalizer::en($title),
                'details' => Normalizer::en(Normalizer::html($row->detail)),
                'sort_order' => (int) $row->serial,
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Scholarship $s, bool $created) => $this->keepSlug($s, $row->slug, $created),
                existing: $this->same(Scholarship::class, ['parent_id' => null, 'title->en' => $title]));

            if ((int) $row->parent_category_id !== 0) {
                $this->report->review("Scholarship \"{$title}\" had a parent category ({$row->parent_category_id}); kept as a scholarship of its own.");
            }
        }

        foreach ($this->rows('scholarships') as $row) {
            $parentId = $this->ids->find('scholarship_categories', $row->scholarship_category_id);
            if ($parentId === null) {
                $this->report->skipped('scholarships', 'scholarship (category) not migrated');

                continue;
            }

            $title = Normalizer::text($row->title);

            $this->upsert('scholarships', $row, Scholarship::class, [
                'parent_id' => $parentId,
                'title' => Normalizer::en($title),
                'details' => Normalizer::en(Normalizer::html($row->detail)),
                'sort_order' => (int) $row->serial,
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Scholarship $s, bool $created) => $this->keepSlug($s, $row->slug, $created),
                existing: $this->same(Scholarship::class, ['parent_id' => $parentId, 'title->en' => $title]));
        }

        $this->report->review('Scholarship calls have no deadline in the legacy data: set "Deadline" by hand where a call is time-bound.');
    }
}
