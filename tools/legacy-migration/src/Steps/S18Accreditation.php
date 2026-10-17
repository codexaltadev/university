<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;
use Modules\Program\Models\Program;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S18R Accreditation & Ranking (DB-06 v1.3 §5): `accreditation_categories` → accreditation.category;
 * `accreditations` → shown in their program (S06), and in its department / faculty when
 * `is_department` / `is_faculty`; `rankings` → one result per row: the agency key is the title
 * without its year (results of one ranking then make a trend), the rank read from the text
 * ("201-300", "150", "3rd") — review, the old system had no rank field; `is_home` → highlighted.
 */
class S18Accreditation extends Step
{
    public static function key(): string
    {
        return 'S18R';
    }

    public static function title(): string
    {
        return 'Accreditation & Ranking';
    }

    public static function requires(): array
    {
        return ['accreditation'];
    }

    protected function handle(): void
    {
        $engine = app(RelationEngine::class);
        if (Schema::connection('legacy')->hasTable('accreditations')) {
            $vocabulary = app(Vocabularies::class)->find(Accreditation::CATEGORY);
            $terms = [];
            foreach ($this->rows('accreditation_categories') as $row) {
                $name = Normalizer::text($row->title);
                $terms[$row->id] = $this->upsert('accreditation_categories', $row, Term::class, [
                    'vocabulary_id' => $vocabulary->id, 'name' => Normalizer::en($name), 'sort_order' => (int) $row->serial, 'is_active' => (bool) $row->status,
                ], after: fn (Term $t, bool $created) => $this->keepSlug($t, Str::slug((string) $name), $created),
                    existing: $this->same(Term::class, ['vocabulary_id' => $vocabulary->id, 'name->en' => $name]));
            }
            foreach ($this->rows('accreditations') as $row) {
                $title = Normalizer::text($row->title);
                $program = $row->department_program_type_id && class_exists(Program::class) ? $this->ids->record('department_program_type', $row->department_program_type_id, Program::class) : null;
                $places = [];
                if ($program) {
                    $places[] = $program->getMorphClass().':'.$program->getKey();
                    $department = $row->is_department ? $program->department : null;
                    if ($department) {
                        $places[] = $department->getMorphClass().':'.$department->getKey();
                    }
                    $faculty = $row->is_faculty ? $department?->faculty ?? $program->department?->faculty : null;
                    if ($faculty) {
                        $places[] = $faculty->getMorphClass().':'.$faculty->getKey();
                    }
                } elseif ($row->department_program_type_id) {
                    $this->report->review("accreditations #{$row->id}: its program is not migrated (run S06 first) — on the main site for now.");
                }
                $this->upsert('accreditations', $row, Accreditation::class, [
                    'title' => Normalizer::en($title),
                    'body' => Normalizer::en(Normalizer::html($row->description)),
                    'website_url' => Normalizer::url($row->link),
                    'sort_order' => max(0, (int) $row->serial),
                    'is_active' => (bool) $row->publication_status,
                ], after: function (Accreditation $a, bool $created) use ($engine, $terms, $row, $places): void {
                    $this->keepSlug($a, $row->slug, $created);
                    $term = $terms[(int) $row->accreditation_category_id] ?? null;
                    $a->syncTerms(Accreditation::CATEGORY, $term ? [$term->id] : []);
                    $engine->sync($a, array_values(array_unique($places)), mainSite: $places === [], user: null);
                }, existing: $this->same(Accreditation::class, ['title->en' => $title]));
            }
        }

        if (Schema::connection('legacy')->hasTable('rankings')) {
            foreach ($this->rows('rankings') as $row) {
                $title = (string) Normalizer::text($row->title);
                $name = trim(preg_replace('/\s*\b(19|20)\d{2}(\s*[-–]\s*\d{2,4})?\b\s*/', ' ', $title)) ?: $title;
                $key = Str::limit(Str::slug($name), 50, '');
                $year = (int) $row->year ?: (preg_match('/\b(19|20)\d{2}\b/', $title.' '.$row->short_description, $m) ? (int) $m[0] : (int) substr((string) $row->created_at, 0, 4));
                $text = (string) Normalizer::text(strip_tags((string) $row->short_description));
                $taken = Ranking::query()->withoutGlobalScopes()->whereNull('deleted_at')->where(['agency_key' => $key, 'year' => $year, 'scope' => 'overall'])
                    ->whereKeyNot($this->ids->record('rankings', $row->id, Ranking::class)?->getKey() ?? 0)->exists();
                if ($taken) {
                    $key = Str::limit($key, 44, '').'-'.$row->id;
                    $this->report->review("rankings #{$row->id} \"{$title}\": a second result for {$year} — kept apart (agency key {$key}); merge by hand if it is the same.");
                }
                $this->upsert('rankings', $row, Ranking::class, [
                    'agency_key' => $key,
                    'agency_name' => Normalizer::en($name),
                    'year' => $year,
                    'scope' => 'overall',
                    'rank_label' => self::rankFrom($text),
                    'summary' => Normalizer::en($text ?: null),
                    'body' => Normalizer::en(Normalizer::html($row->long_description)),
                    'ranking_url' => Normalizer::url($row->ranking_url),
                    'sort_order' => max(0, (int) $row->serial),
                    'is_active' => (bool) $row->publication_status,
                ], after: fn (Ranking $r) => $engine->sync($r, [], mainSite: true, highlights: $row->is_home ? ['site:0' => ['order' => max(0, (int) $row->serial)]] : [], user: null));
            }
            $this->report->review('Rankings: the old system had no rank field — the rank was read from the text; check each result\'s "Rank" and its kind (overall / subject / region).');
        }
        $this->report->review('Accreditation and ranking logos wait for the files (S03).');
    }

    /** "(201-300)" → "201–300", "150 no." → "150", "3rd among …" → "3rd"; else "Ranked". */
    public static function rankFrom(string $text): string
    {
        if (preg_match('/(\d{1,4})\s*[-–]\s*(\d{1,4})/', $text, $m)) {
            return $m[1].'–'.$m[2];
        }
        if (preg_match('/\b(\d{1,4}(?:st|nd|rd|th))\b/i', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/\btop\s+(\d{2,4})\b/i', $text, $m)) {
            return 'Top '.$m[1];
        }
        if (preg_match('/\b(\d{2,4})\s*(?:no\.?|position|place)\b/i', $text, $m)) {
            return $m[1];
        }

        return 'Ranked';
    }
}
