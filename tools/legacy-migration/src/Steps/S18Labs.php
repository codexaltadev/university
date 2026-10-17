<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Modules\Page\Models\Page;
use Platform\Assignments\AssignmentEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S18L Labs (DB-06 v1.3 §3): `lab_facilities` → labs under their department (S04), old web address
 * kept; `assign_person_*` → a manual person "in_charge"; `instrument` → equipment in short;
 * `lab_tools` / `lab_scopes` / `lab_projects` / `lab_products` + their categories → the lab's items
 * (categories shared: lab.{type}-category); `lab_articles` → the lab's own pages. The lab's
 * gallery, news, committee, menus and feature blocks (`lab_mid_sections`) are reported for those
 * modules / by hand; pictures wait for S03. Run S18P again afterwards for the labs' partners.
 */
class S18Labs extends Step
{
    public const ITEMS = [
        'lab_tools' => ['equipment', 'lab_tool_categories', 'lab_tool_category_id'],
        'lab_scopes' => ['scope', 'lab_scope_categories', 'lab_scope_category_id'],
        'lab_projects' => ['project', 'lab_project_categories', 'lab_project_category_id'],
        'lab_products' => ['product', 'lab_product_categories', 'lab_product_category_id'],
    ];

    public static function key(): string
    {
        return 'S18L';
    }

    public static function title(): string
    {
        return 'Labs';
    }

    public static function requires(): array
    {
        return ['lab'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('lab_facilities')) {
            $this->report->review('No lab_facilities table in the legacy database: nothing to migrate.');

            return;
        }
        $labs = [];
        foreach ($this->rows('lab_facilities') as $i => $row) {
            $name = Normalizer::text($row->lab_name);
            if ($name === null) {
                $this->report->skipped('lab_facilities', 'no name');

                continue;
            }
            $department = $row->department_id && class_exists(Department::class) ? $this->ids->record('departments', $row->department_id, Department::class) : null;
            if ($row->department_id && $department === null) {
                $this->report->review("lab_facilities #{$row->id}: its department is not migrated (run S04 first) — the lab is at /lab/… for now.");
            }
            $video = Normalizer::url($row->promotional_video);
            $labs[$row->id] = $this->upsert('lab_facilities', $row, Lab::class, [
                'department_id' => $department?->getKey(),
                'name' => Normalizer::en($name),
                'location' => Normalizer::en(Normalizer::text($row->lab_location)),
                'summary' => Normalizer::en(Str::limit((string) Normalizer::text(strip_tags((string) $row->short_description)), 500) ?: null),
                'body' => Normalizer::en(Normalizer::html($row->short_description)),
                'equipment_summary' => Normalizer::en(Normalizer::html($row->instrument)),
                'video_url' => $video && preg_match('#^https?://#i', $video) ? $video : null,
                'website_url' => Normalizer::url($row->website_link),
                'sort_order' => $i,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Lab $lab, bool $created) use ($row): void {
                $this->keepSlug($lab, str_replace('_', '-', (string) $row->slug), $created);
                if (filled($row->assign_person_name) && $lab->people('in_charge')->isEmpty()) {
                    $person = $this->manualPerson('lab_facilities@person', $row, $row->assign_person_name, $row->assign_person_designation, $row->assign_person_email, $row->assign_person_phone);
                    app(AssignmentEngine::class)->assign($person, $lab, 'in_charge');
                }
            }, existing: $this->same(Lab::class, ['name->en' => $name, 'department_id' => $department?->getKey()]));
        }

        foreach (self::ITEMS as $table => [$type, $categoryTable, $categoryColumn]) {
            if (! Schema::connection('legacy')->hasTable($table)) {
                continue;
            }
            $terms = $this->categories($categoryTable, LabItem::vocabulary($type));
            foreach ($this->rows($table) as $i => $row) {
                $lab = $labs[(int) $row->lab_facility_id] ?? null;
                $title = Normalizer::text($row->title);
                if ($lab === null || $title === null) {
                    $this->report->skipped($table, $lab === null ? 'its lab is not there' : 'no title');

                    continue;
                }
                $this->upsert($table, $row, LabItem::class, [
                    'lab_id' => $lab->id,
                    'item_type' => $type,
                    'title' => Normalizer::en($title),
                    'description' => Normalizer::en(Normalizer::html($row->detail ?? $row->description ?? null)),
                    'student_names' => Normalizer::en(Normalizer::text($row->author_name ?? null)),
                    'student_ids' => Normalizer::text($row->student_id ?? null),
                    'options' => isset($row->show_side) ? ['side' => $row->show_side] : null,
                    'sort_order' => (int) ($row->serial ?? $i),
                    'is_active' => (bool) $row->publication_status,
                ], after: function (LabItem $item, bool $created) use ($row, $terms, $categoryColumn): void {
                    $this->keepSlug($item, $row->slug ?? null, $created);
                    $term = $terms[(int) ($row->{$categoryColumn} ?? 0)] ?? null;
                    $item->syncTerms(LabItem::vocabulary($item->item_type), $term ? [$term->id] : []);
                }, existing: $this->same(LabItem::class, ['lab_id' => $lab->id, 'item_type' => $type, 'title->en' => $title]));
            }
        }

        if (Schema::connection('legacy')->hasTable('lab_articles') && class_exists(Page::class)) {
            foreach ($this->rows('lab_articles') as $row) {
                $lab = $labs[(int) $row->lab_facility_id] ?? null;
                $title = Normalizer::text($row->title);
                if ($lab === null || $title === null) {
                    $this->report->skipped('lab_articles', 'its lab is not there');

                    continue;
                }
                $this->upsert('lab_articles', $row, Page::class, [
                    'owner_type' => $lab->getMorphClass(),
                    'owner_id' => $lab->getKey(),
                    'parent_id' => null,
                    'title' => Normalizer::en($title),
                    'content' => ['en' => [['type' => 'text', 'data' => ['body' => Normalizer::html($row->detail) ?? '<p></p>']]]],
                    'template' => 'default',
                    'sort_order' => 0,
                    'is_active' => (bool) $row->publication_status,
                ], after: fn (Page $p, bool $created) => $this->keepSlug($p, $row->slug, $created),
                    existing: $this->same(Page::class, ['owner_type' => $lab->getMorphClass(), 'owner_id' => $lab->getKey(), 'title->en' => $title]));
            }
        }

        foreach (['lab_galleries' => 'Gallery (after S03)', 'lab_news' => 'News', 'lab_committees' => 'Committee', 'lab_research' => 'Research', 'lab_menus' => 'the lab\'s Menu', 'lab_app_menus' => 'the "Quick Links (icon)" menu', 'lab_mid_sections' => 'feature blocks on the lab\'s homepage', 'lab_section_summaries' => 'section intro texts on the lab\'s homepage'] as $table => $where) {
            if (Schema::connection('legacy')->hasTable($table) && ($count = (int) $this->legacy()->table($table)->count()) > 0) {
                $this->report->review("{$table} ({$count}): set up in {$where} with \"Show in\" the lab.");
            }
        }
        $this->report->review('Lab logos, covers and item pictures wait for the files (S03). Run S18P again so the labs\' partners (mps) move into their labs.');
    }

    /** @return array<int, Term> legacy category id → term of the shared vocabulary */
    protected function categories(string $table, string $vocabularyKey): array
    {
        if (! Schema::connection('legacy')->hasTable($table)) {
            return [];
        }
        $vocabulary = app(Vocabularies::class)->find($vocabularyKey);
        $terms = [];
        foreach ($this->rows($table) as $row) {
            $name = Normalizer::text($row->title);
            $terms[$row->id] = $this->upsert($table, $row, Term::class, [
                'vocabulary_id' => $vocabulary->id, 'name' => Normalizer::en($name), 'sort_order' => (int) $row->id, 'is_active' => (bool) ($row->publication_status ?? true),
            ], after: fn (Term $t, bool $created) => $this->keepSlug($t, $row->slug ?? Str::slug((string) $name), $created),
                existing: $this->same(Term::class, ['vocabulary_id' => $vocabulary->id, 'name->en' => $name]));
        }

        return $terms;
    }
}
