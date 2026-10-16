<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Faq\Models\Faq;
use Modules\Office\Models\Office;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S17 (FAQ part, DB-05 v1.1 §18): the 9 categories (slugs kept) and 1,309 questions — shown in
 * their department (or the office an office-"department" became in S10); those of no department
 * (128) on the main site. Run after S04 and S10.
 */
class S17Faqs extends Step
{
    public static function key(): string
    {
        return 'S17F';
    }

    public static function title(): string
    {
        return 'FAQ: categories and questions';
    }

    public static function requires(): array
    {
        return ['faq'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('faqs')) {
            $this->report->review('No faqs table in the legacy database: nothing to migrate.');

            return;
        }

        $terms = [];
        $vocabulary = app(Vocabularies::class)->find(Faq::CATEGORY);
        foreach ($this->rows('faq_categories', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $name = Normalizer::text($row->category_name);
            Vocabularies::ensureTerms(Faq::CATEGORY, [['name' => ['en' => $name], 'slug' => $row->category_slug]]);
            $terms[$row->id] = Term::query()->where('vocabulary_id', $vocabulary->id)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        $engine = app(RelationEngine::class);
        $noPlace = 0;
        foreach ($this->rows('faqs', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $question = Normalizer::text(strip_tags((string) $row->question));
            if (blank($question)) {
                $this->report->skipped('faqs', 'no question');

                continue;
            }

            $place = $row->department_id
                ? ($this->ids->record('departments', $row->department_id, Department::class) ?? $this->ids->record('departments', $row->department_id, Office::class))
                : null;
            $targets = $place ? [$place->getMorphClass().':'.$place->getKey()] : [];
            $noPlace += $row->department_id && ! $place ? 1 : 0;

            $this->upsert('faqs', $row, Faq::class, [
                'question' => Normalizer::en($question),
                'answer' => Normalizer::en(Normalizer::html($row->answer) ?? ''),
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: function (Faq $faq) use ($terms, $row, $targets, $engine): void {
                $faq->syncTerms(Faq::CATEGORY, array_filter([$terms[$row->faq_category_id] ?? null]));
                // Legacy is_home is never "1"; the legacy /faq showed the FAQs of no department → the main site's
                $engine->sync($faq, $targets, mainSite: $row->is_home === '1' || blank($row->department_id), user: null);
            });
        }

        if ($noPlace > 0) {
            $this->report->review("{$noPlace} FAQs belonged to a legacy department that is not migrated (run S04 and S10 first): shown nowhere but the main site, if at all.");
        }
    }
}
