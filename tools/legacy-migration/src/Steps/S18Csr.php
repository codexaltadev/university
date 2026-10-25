<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Csr\Models\Activity;
use Modules\Csr\Support\Categories;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S18C CSR (DB-06 v1.3 §7): `csr_categories` → csr.category terms with their parent and
 * description (pictures wait for S03); `csrs` → activities in their category, on the main site
 * when `is_home` or not placed anywhere else; `is_featured` → highlighted. The date is the day it
 * was entered (the old system had none). Category pictures from the Media Library once S03 group
 * `csr` has them (run S18C again after it); DIU's /csr-activities[/{slug}] → 301 here.
 */
class S18Csr extends Step
{
    public static function key(): string
    {
        return 'S18C';
    }

    public static function title(): string
    {
        return 'CSR';
    }

    public static function requires(): array
    {
        return ['csr'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('csrs')) {
            $this->report->review('No csrs table in the legacy database: nothing to migrate.');

            return;
        }
        $vocabulary = app(Vocabularies::class)->find(Activity::CATEGORY);
        $terms = [];
        $categories = $this->rows('csr_categories');
        foreach ($categories->sortBy(fn ($c) => (int) $c->parent_category_id) as $row) {   // parents first
            $name = Normalizer::text($row->title);
            $parent = (int) $row->parent_category_id ? ($terms[(int) $row->parent_category_id] ?? null) : null;
            $terms[$row->id] = $this->upsert('csr_categories', $row, Term::class, [
                'vocabulary_id' => $vocabulary->id,
                'parent_id' => $parent?->id,
                'name' => Normalizer::en($name),
                'description' => Normalizer::en(Normalizer::text(strip_tags((string) $row->short_description))),
                'sort_order' => (int) $row->serial,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Term $t, bool $created) use ($row, $name): void {
                $this->keepSlug($t, $row->slug ?: Str::slug((string) $name), $created);
                // the picture S03 put in the library (id map "files", key = its path)
                $item = filled($row->image) ? $this->ids->record('files', (string) Normalizer::filePath((string) $row->image), MediaItem::class) : null;
                if ($item) {
                    app(Categories::class)->setImage($t, $item->id);
                }
            }, existing: $this->same(Term::class, ['vocabulary_id' => $vocabulary->id, 'name->en' => $name]));
        }

        $engine = app(RelationEngine::class);
        foreach ($this->rows('csrs', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text($row->csr_title);
            if ($title === null) {
                $this->report->skipped('csrs', 'no title');

                continue;
            }
            $video = Normalizer::url($row->video_link);
            $this->upsert('csrs', $row, Activity::class, [
                'title' => Normalizer::en($title),
                'body' => Normalizer::en(Normalizer::html($row->csr_content)),
                'external_url' => Normalizer::url($row->csr_url),
                'video_url' => $video,
                'occurred_on' => $row->created_at ? substr((string) $row->created_at, 0, 10) : null,
                'active_at' => $row->created_at,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Activity $activity, bool $created) use ($engine, $terms, $row): void {
                $this->keepSlug($activity, $row->csr_slug, $created);
                $term = $terms[(int) $row->csr_category_id] ?? null;
                $activity->syncTerms(Activity::CATEGORY, $term ? [$term->id] : []);
                $engine->sync($activity, [], mainSite: true, highlights: $row->is_featured ? ['site:0' => ['order' => 0]] : [], user: null);
                if (filled($row->csr_slug)) {
                    $this->oldAddress('/csr-activities/'.$row->csr_slug, $activity);   // DIU's activity address
                }
            }, existing: $this->same(Activity::class, ['title->en' => $title]));
        }
        $this->oldAddress('/csr-activities', '/'.Activity::PATH);
        $this->report->review('CSR pictures (photo, photo_two) and category pictures wait for the files (S03); set the category pictures in Services › CSR category pictures.');
    }
}
