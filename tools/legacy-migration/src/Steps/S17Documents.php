<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Documents\Models\Document;
use Modules\Program\Models\Program;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S17D Documents (DB-05 v1.9 §12, §18): `flipbooks` (+ categories) → flipbook documents on the main
 * site (the old ZIP books need their original PDF — migration plan 7.7); `prospectuses` → prospectus
 * of their program ("Fall 2026"); `charter_guidebooks` (+ categories) → guidebook / charter of their
 * department; `policies` → policy (approval date). Categories → documents.category. Files are not
 * copied: once S03 has put a legacy file in the Media Library (id map "files", key = its path) the
 * document uses it — run again after S03. Run after S04, S06, S10.
 */
class S17Documents extends Step
{
    protected int $waiting = 0;

    /** @var array<string, int|null> */
    protected array $terms = [];

    public static function key(): string
    {
        return 'S17D';
    }

    public static function title(): string
    {
        return 'Documents';
    }

    public static function requires(): array
    {
        return ['documents'];
    }

    protected function handle(): void
    {
        if (Schema::connection('legacy')->hasTable('flipbooks')) {
            $categories = Schema::connection('legacy')->hasTable('flipbook_categories')
                ? $this->rows('flipbook_categories')->keyBy('id') : collect();
            foreach ($this->rows('flipbooks') as $row) {
                $category = Normalizer::text($categories[$row->flipbook_category_id]->title ?? null);
                $this->document('flipbooks', $row, [
                    'document_type' => str_contains(strtolower((string) $category), 'report') ? 'report' : 'other',
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'show_as_flipbook' => true,
                    'sort_order' => (int) $row->serial,
                    'is_active' => (bool) $row->publication_status,
                ], file: null, thumbnail: $row->thumb, places: [], main: true, category: $category, slug: $row->slug);
            }
            $this->report->review('The 16 flipbooks were ZIP books: put their original PDF on each under Content › Documents (migration plan 7.7) — the flipbook then works by itself.');
        }

        if (Schema::connection('legacy')->hasTable('prospectuses')) {
            $semesters = Schema::connection('legacy')->hasTable('semesters') ? $this->legacy()->table('semesters')->pluck('semester_name', 'id') : collect();
            foreach ($this->rows('prospectuses', fn ($q) => $q->whereNull('deleted_at')) as $row) {
                $program = $this->ids->record('department_program_type', $row->department_program_type_id, Program::class);
                $session = trim(Str::before((string) ($semesters[$row->semester_id] ?? ''), ' Semester').' '.$row->year);
                $place = $program ?? $this->department($row->department_id);
                $this->document('prospectuses', $row, [
                    'document_type' => 'prospectus',
                    'title' => ['en' => trim('Prospectus '.($program?->getTranslation('name', 'en', false) ?? '').' '.$session)],
                    'session_label' => $session ?: null,
                    'program_id' => $program?->getKey(),
                    'is_active' => (bool) $row->publication_status,
                ], file: $row->prospectus_file, thumbnail: $row->prospectus_thumbs, places: array_filter([$place]), main: (string) $row->is_home === '1');
            }
        }

        if (Schema::connection('legacy')->hasTable('charter_guidebooks')) {
            $categories = Schema::connection('legacy')->hasTable('charter_guidebook_categories')
                ? $this->legacy()->table('charter_guidebook_categories')->pluck('title', 'id') : collect();
            foreach ($this->rows('charter_guidebooks') as $row) {
                $category = Normalizer::text($categories[$row->charter_guidebook_category_id] ?? null);
                $place = $this->department($row->department_id);
                $this->document('charter_guidebooks', $row, [
                    'document_type' => str_contains(strtolower((string) $category), 'charter') ? 'charter' : 'guidebook',
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'is_active' => (bool) $row->publication_status,
                ], file: $row->file_name, thumbnail: null, places: array_filter([$place]), main: $place === null, category: $category);
            }
        }

        if (Schema::connection('legacy')->hasTable('policies')) {
            foreach ($this->rows('policies', fn ($q) => $q->whereNull('deleted_at')) as $row) {
                $place = $this->department($row->department_id);
                $this->document('policies', $row, [
                    'document_type' => 'policy',
                    'title' => Normalizer::en(Normalizer::text($row->policy_title)),
                    'approved_on' => $row->approve_date,
                    'is_active' => (bool) $row->publication_status,
                ], file: $row->policy_file_name, thumbnail: $row->icon, places: array_filter([$place]), main: $place === null);
            }
            $this->report->review('policies: DIU\'s one row is a test entry ("This is test Document") — delete it under Content › Documents if it is not wanted.');
        }

        if ($this->waiting > 0) {
            $this->report->review("{$this->waiting} document files wait for the files (S03): run S17D again after S03 — documents then use the Media Library items, no copies.");
        }
    }

    /** @param list<Model> $places */
    protected function document(string $table, object $row, array $attributes, ?string $file, ?string $thumbnail, array $places, bool $main, ?string $category = null, ?string $slug = null): void
    {
        if (blank($attributes['title']['en'] ?? null)) {
            $this->report->skipped($table, 'no title');

            return;
        }

        $this->upsert($table, $row, Document::class, $attributes, after: function (Document $document, bool $created) use ($file, $thumbnail, $places, $main, $category, $slug): void {
            app(RelationEngine::class)->sync($document, array_map(fn (Model $p): string => $p->getMorphClass().':'.$p->getKey(), $places), mainSite: $main, user: null);
            if ($category) {
                $document->syncTerms(Document::CATEGORY, array_filter([$this->term($category)]));
            }
            if ($slug) {
                $this->keepSlug($document, Str::slug($slug), $created);
            }
            foreach (['file' => $file, 'thumbnail' => $thumbnail] as $field => $path) {
                if (blank($path)) {
                    continue;
                }
                $item = $this->ids->record('files', (string) $path, MediaItem::class);
                $item ? $document->syncMedia($field, [$item->id]) : ($field === 'file' ? $this->waiting++ : null);
            }
        });
    }

    protected function term(string $name): ?int
    {
        if (! array_key_exists($name, $this->terms)) {
            Vocabularies::ensureTerms(Document::CATEGORY, [['name' => ['en' => $name], 'slug' => Str::slug($name)]]);
            $vocabulary = app(Vocabularies::class)->find(Document::CATEGORY)->id;
            $this->terms[$name] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        return $this->terms[$name];
    }

    protected function department(int|string|null $id): ?Model
    {
        if (blank($id)) {
            return null;
        }
        $office = 'Modules\\Office\\Models\\Office';

        return $this->ids->record('departments', (int) $id, Department::class)
            ?? (class_exists($office) ? $this->ids->record('departments', (int) $id, $office) : null);
    }
}
