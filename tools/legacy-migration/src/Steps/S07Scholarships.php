<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Scholarship\Models\Scholarship;
use Platform\Settings\Settings;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

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

        $this->accordions();
    }

    /**
     * DIU's "Waiver and Scholarship" page (DB-03 v1.4 §11.3): accordion_categories "scholarship" (its top / bottom
     * text → Settings › Scholarships) with five sub-categories → scholarship categories; each accordion → a
     * scholarship in its category.
     */
    protected function accordions(): void
    {
        if (! Schema::connection('legacy')->hasTable('accordion_categories')) {
            return;
        }

        $categories = $this->rows('accordion_categories');
        $root = $categories->first(fn ($c) => (int) $c->parent_category_id === 0);
        if ($root) {
            app(Settings::class)->save('scholarship', [
                'intro' => Normalizer::en(Normalizer::html($root->top_content)),
                'outro' => Normalizer::en(Normalizer::html($root->bottom_content)),
            ], translatable: ['intro', 'outro']);
        }

        $terms = [];
        foreach ($categories->filter(fn ($c) => (int) $c->parent_category_id !== 0) as $row) {
            $name = Normalizer::text($row->title);
            Vocabularies::ensureTerms(Scholarship::CATEGORY, [['name' => ['en' => $name], 'slug' => $row->slug]]);
            $terms[$row->id] = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(Scholarship::CATEGORY)->id)
                ->get()->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        foreach ($this->rows('accordions') as $row) {
            $title = Normalizer::text(strip_tags((string) $row->title));
            $term = $terms[$row->accordion_category_id] ?? null;

            $this->upsert('accordions', $row, Scholarship::class, [
                'parent_id' => null,
                'title' => Normalizer::en($title),
                'details' => Normalizer::en(Normalizer::html($row->description)),
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Scholarship $s) => $term ? $s->syncTerms(Scholarship::CATEGORY, [$term]) : null,
                existing: $this->same(Scholarship::class, ['parent_id' => null, 'title->en' => $title]));
        }
    }
}
