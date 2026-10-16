<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Notice\Models\Notice;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Views\ViewCounter;

/**
 * S16T Notices (DB-05 v1.4 §5, §18): DIU's `notices` — shown in their department (S04, or the
 * office an office-"department" became in S10), on the main site when `is_home` (Q-26), dated
 * when written (active_at), view counts kept, `notice_categories` → notice.category. The 3,532
 * `notice_files` wait for the files (S03): their titles go on the attachments then. Run after S04, S10.
 */
class S16Notices extends Step
{
    public static function key(): string
    {
        return 'S16T';
    }

    public static function title(): string
    {
        return 'Notices';
    }

    public static function requires(): array
    {
        return ['notice'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('notices')) {
            $this->report->review('No notices table in the legacy database: nothing to migrate.');

            return;
        }

        $vocabulary = app(Vocabularies::class)->find(Notice::CATEGORY)->id;
        $categories = [];
        foreach ($this->rows('notice_categories', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $name = Normalizer::text($row->name);
            Vocabularies::ensureTerms(Notice::CATEGORY, [['name' => ['en' => $name], 'slug' => Str::slug($name)]]);
            $categories[$row->id] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        $engine = app(RelationEngine::class);
        $views = app(ViewCounter::class);
        $office = 'Modules\\Office\\Models\\Office';
        $nowhere = 0;

        foreach ($this->rows('notices', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text($row->title);
            if (blank($title)) {
                $this->report->skipped('notices', 'no title');

                continue;
            }
            $place = $row->department_id ? ($this->ids->record('departments', $row->department_id, Department::class)
                ?? (class_exists($office) ? $this->ids->record('departments', $row->department_id, $office) : null)) : null;
            $main = (string) $row->is_home === '1';
            $nowhere += ! $place && ! $main ? 1 : 0;

            $this->upsert('notices', $row, Notice::class, [
                'title' => Normalizer::en($title),
                'body' => Normalizer::en(Normalizer::html($row->content)),
                'active_at' => $row->created_at,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Notice $notice, bool $created) use ($row, $place, $main, $categories, $engine, $views): void {
                $this->keepSlug($notice, Str::slug((string) $row->slug), $created);
                $notice->syncTerms(Notice::CATEGORY, array_filter([$categories[$row->notice_category_id] ?? null]));
                $engine->sync($notice, $place ? [$place->getMorphClass().':'.$place->getKey()] : [], mainSite: $main, user: null);
                $views->seed($notice, (int) $row->view_counter);
            });
        }

        if (Schema::connection('legacy')->hasTable('notice_files')) {
            $this->report->review($this->legacy()->table('notice_files')->whereNull('deleted_at')->count().' notice files (with their titles) wait for the files (S03).');
        }
        if ($nowhere > 0) {
            $this->report->review("{$nowhere} notices belong to a department that is not migrated and were not on DIU's homepage: shown nowhere (run S04 and S10 first).");
        }
    }
}
