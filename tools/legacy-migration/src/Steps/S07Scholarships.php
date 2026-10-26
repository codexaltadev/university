<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Scholarship\Models\Scholarship;
use Modules\Scholarship\Settings\ScholarshipSettings;
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

    /** DIU's /scholarship sidebar (DIUFronend scholarship/layout): the named scholarships are articles here (/article/{slug}) */
    public const SIDE = [
        'chairman-endowment-fund-scholarship' => 'Chairman Endowment Fund Scholarship',
        'lutfar-rahman-scholarship' => 'Lutfar Rahman Scholarship',
        'razia-begum-scholarship' => 'Razia Begum Scholarship',
        'mofiz-uddin-scholarship' => 'Mofiz Uddin Majumder Scholarship',
        'delwar-hussain-scholarship' => 'Delwar Hussain Chowdhury Scholarship',
        'late-hafez-and-late-mrs.-jarina-begum-scholarship' => 'Late Hafez Md. Abdur Rahim and Late Mrs. Jarina Begum Scholarship',
        'late-professor-dr.-ahmad-and-late-mrs.-mahbuba-hossain-scholarship' => 'Late Professor Dr. Ahmad Husain and Late Mrs. Mahbuba Hossain Scholarship',
        'mrs-wasia-rahman-and-professor-dr-m-lutfar-rahman-scholarship-fund' => 'Mrs. Wasia Rahman and Professor Dr. M. Lutfar Rahman Scholarship',
    ];

    /** DIU's /scholarship Quick Links */
    public const QUICK = [['Notice', '/notices'], ['Tuition Fees', '/tuition-fees'], ['Waiver Calculator', '/waiver-calculator'], ['Waiver & Scholarship', '/scholarships']];

    protected function handle(): void
    {
        $this->page();

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

    /**
     * Settings › Scholarships as DIU's /scholarship: the named scholarships and the FAQ beside the list, the Quick Links —
     * only while still empty or as installed; DIU's /scholarship/{slug} addresses lead to the articles (301).
     */
    protected function page(): void
    {
        $now = settings()->group('scholarship');
        $installed = app(ScholarshipSettings::class)->defaults();
        $links = fn (array $list): array => array_map(fn (array $l): array => ['label' => ['en' => $l[0]], 'url' => $l[1]], $list);

        settings()->save('scholarship', array_filter([
            'side_links' => blank($now['side_links'] ?? null) ? $links([
                ...array_map(fn (string $slug, string $name): array => [$name, '/article/'.$slug], array_keys(self::SIDE), self::SIDE),
                ['Waiver and Scholarship FAQ', '/faq'],
            ]) : null,
            'quick_links' => ($now['quick_links'] ?? null) === $installed['quick_links'] ? $links(self::QUICK) : null,
        ], fn ($v) => $v !== null));

        $this->oldAddress('/scholarship/diu-scholarship', '/'.Scholarship::PATH);
        foreach (array_keys(self::SIDE) as $slug) {
            $this->oldAddress('/scholarship/'.$slug, '/article/'.$slug);
        }
    }
}
