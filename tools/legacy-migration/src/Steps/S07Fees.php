<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Program\Models\FeeCategory;
use Modules\Program\Models\Tuition;
use Modules\Program\Models\TuitionLine;
use Modules\Waiver\Models\Board;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S07 — Tuition (DB-03 §11.1 v1.1, §15): tuition_categories → fee categories, tuitions →
 * tuitions with their fees as lines. DIU's totals are kept exactly as entered (they do
 * not follow from the lines), so every migrated tuition has "totals overridden". A max
 * range is taken only where the legacy calculator shows one (max total and max credit).
 * Waiver rules, boards and categories follow in the waiver part of this step.
 */
class S07Fees extends Step
{
    public const YEARS = ['first_year' => '1st Year', 'second_year' => '2nd Year', 'third_year' => '3rd Year', 'fourth_year' => '4th Year'];

    public static function key(): string
    {
        return 'S07';
    }

    public static function title(): string
    {
        return 'Tuition fees and waiver rules';
    }

    public static function requires(): array
    {
        return ['program', 'waiver'];
    }

    protected function handle(): void
    {
        foreach ($this->rows('tuition_categories') as $row) {
            // "local-tuition" → "local", "international-tuition" → "international" (/tuition-fees?category=international)
            $code = Str::limit(Str::before(Str::slug((string) $row->category_code), '-tuition'), 20, '');

            $this->upsert('tuition_categories', $row, FeeCategory::class, [
                'name' => Normalizer::en(Normalizer::text($row->category_name)),
                'code' => $code,
                'currency' => Str::contains(Str::lower($row->category_name.' '.$row->category_code), 'international') ? 'USD' : 'BDT',
                'sort_order' => (int) $row->id,
                'is_active' => (bool) $row->publication_status,
            ], existing: $this->same(FeeCategory::class, ['code' => $code]));
            $this->report->review("Fee category \"{$row->category_name}\": code \"{$code}\" (was \"{$row->category_code}\").");
        }

        $rows = $this->rows('tuitions');
        $siblings = $rows->groupBy(fn ($r) => $r->department_program_type_id.'-'.$r->tuition_category_id);

        foreach ($rows as $row) {
            $programId = $this->ids->find('department_program_type', $row->department_program_type_id);
            $categoryId = $this->ids->find('tuition_categories', $row->tuition_category_id);

            if ($programId === null || $categoryId === null) {
                $this->report->skipped('tuitions', $programId === null ? 'program not migrated' : 'category not migrated');

                continue;
            }

            [$creditMin, $creditMax] = $this->credits($row->credit_hours);
            $ranged = $this->money($row->max_total_fees) > 0 && $this->money($row->total_fees) > 0 && (int) $row->max_credit > 0;   // the legacy calculator's own condition
            $year = Normalizer::text($row->year);

            $tuition = $this->upsert('tuitions', $row, Tuition::class, [
                'program_id' => $programId,
                'fee_category_id' => $categoryId,
                'label' => $siblings[$row->department_program_type_id.'-'.$row->tuition_category_id]->count() > 1 ? Normalizer::en($year) : null,
                'credit_min' => $creditMin,
                'credit_max' => $ranged ? (float) $row->max_credit : $creditMax,
                'total_terms' => ctype_digit(trim((string) $row->total_semester)) ? (int) $row->total_semester : null,
                'duration_label' => Normalizer::en($year),
                'tuition_min' => $this->money($row->total_tuition_fees),
                'tuition_max' => $ranged ? $this->money($row->max_tuition_fees) : null,
                'total_min' => $this->money($row->total_fees),
                'total_max' => $ranged ? $this->money($row->max_total_fees) : null,
                'totals_overridden' => true,
                'prospectus_url' => Normalizer::url($row->prospectus_link),
                'sort_order' => (int) ($row->tuition_serial ?? $row->id),
                'is_active' => (bool) $row->publication_status,
            ], existing: $this->same(Tuition::class, ['program_id' => $programId, 'fee_category_id' => $categoryId]));

            $this->line($tuition, $row, 'admission', TuitionLine::ADMISSION, 'Admission fee', $row->admission_fees);
            foreach (self::YEARS as $column => $label) {
                $this->line($tuition, $row, $column, TuitionLine::YEAR, $label, $row->{$column});
            }
            $this->line($tuition, $row, 'semester', TuitionLine::TERM, 'Per semester', $row->semester_cost);
            $this->line($tuition, $row, 'other', TuitionLine::OTHER, 'Other fees', $row->other_fee);

            if (filled($row->majors)) {
                $this->report->archive("majors of tuition {$row->id} (program {$row->department_program_type_id})", (string) $row->majors);
            }
            if (! ctype_digit(trim((string) $row->total_semester)) && filled($row->total_semester)) {
                $this->report->review("Tuition {$row->id}: semesters \"{$row->total_semester}\" is a range — kept in the duration only.");
            }
        }

        $this->waivers();
    }

    /** Legacy program types → program levels (DB-03 §15.1). */
    public const LEVELS = [1 => 'Undergraduate', 2 => 'Graduate'];

    /** Waiver categories the legacy form asks a Diploma CGPA for (`checkIfWaiverCategoryIs18`). */
    public const DIPLOMA_CATEGORIES = [18];

    /** Categories whose rules give max_waiver_rate when HSC > SSC (legacy WaiverApiController: 19, 21). */
    public const IMPROVED_CATEGORIES = [19, 21];

    public const GENDERS = [1 => 'male', 2 => 'female'];

    /** Boards, waiver categories (+ includes) and waiver rules with their conditions (DB-03 §11.2 v1.1). */
    protected function waivers(): void
    {
        $levels = collect(self::LEVELS)->map(fn (string $name): int => $this->levelId($name));

        foreach ($this->rows('boards') as $row) {
            $level = $levels[$row->program_type_id] ?? null;
            if ($level === null || $row->deleted_at !== null) {
                $this->report->skipped('boards', $row->deleted_at !== null ? 'deleted' : 'unknown program type');

                continue;
            }

            $this->upsert('boards', $row, Board::class, [
                'level_term_id' => $level,
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'sort_order' => (int) $row->id,
                'is_active' => (string) $row->is_active === '1',
            ], existing: $this->same(Board::class, ['level_term_id' => $level, 'name->en' => Normalizer::text($row->name)]));
        }

        $categories = $this->rows('waiver_categories');
        foreach ($categories as $row) {
            $level = $levels[$row->program_type_id] ?? null;
            if ($level === null || $row->deleted_at !== null) {
                $this->report->skipped('waiver_categories', $row->deleted_at !== null ? 'deleted' : 'unknown program type');

                continue;
            }

            $this->upsert('waiver_categories', $row, WaiverCategory::class, [
                'level_term_id' => $level,
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'uses_diploma_result' => in_array((int) $row->id, self::DIPLOMA_CATEGORIES, true),
                'sort_order' => (int) $row->id,
                'is_active' => (string) $row->is_active === '1',
            ], existing: $this->same(WaiverCategory::class, ['level_term_id' => $level, 'name->en' => Normalizer::text($row->name)]));
        }

        foreach ($categories as $row) {   // "Female" also matches "Result Based" …
            if ($category = $this->ids->record('waiver_categories', $row->id, WaiverCategory::class)) {
                $included = collect(json_decode((string) $row->waiver_categories, true) ?: [])
                    ->map(fn ($id) => $this->ids->find('waiver_categories', $id))->filter()->values()->all();
                $category->includes()->sync($included);
            }
        }

        $pivot = fn (string $table, string $column) => $this->legacy()->table($table)->get()->groupBy('waiver_id')
            ->map(fn ($rows) => $rows->pluck($column)->map(fn ($v): int => (int) $v)->values()->all());   // natural order, as the legacy app reads it
        $boards = $pivot('board_waiver', 'board_id');
        $genders = $pivot('gender_waiver', 'gender_id');
        $tuitions = $pivot('tuition_waiver', 'tuition_id');
        $ruleCategories = $pivot('waiver_waiver_category', 'waiver_category_id');
        $this->report->source('faculty_waiver', (int) $this->legacy()->table('faculty_waiver')->count());
        $this->report->skipped('faculty_waiver', 'not migrated: the legacy calculator ignores it (DB-03 v1.1 #6)');

        foreach ($this->rows('waivers') as $row) {
            $level = $levels[$row->program_type_id] ?? null;
            $feeCategory = $this->ids->find('tuition_categories', $row->tuition_category_id);

            if ($level === null || $feeCategory === null || $row->deleted_at !== null) {
                $this->report->skipped('waivers', $row->deleted_at !== null ? 'deleted' : 'unknown program type or tuition category');

                continue;
            }

            $international = (int) $row->tuition_category_id === 2;
            $cats = $ruleCategories[$row->id] ?? [];
            $improved = $row->max_waiver_rate !== null && array_intersect($cats, self::IMPROVED_CATEGORIES) !== [];

            if ($row->max_waiver_rate !== null && ! $improved) {
                $this->report->review("Waiver rule {$row->id}: max_waiver_rate {$row->max_waiver_rate} has no effect in the legacy calculator (no category 19 / 21) — not kept.");
            }

            $rule = $this->upsert('waivers', $row, WaiverRule::class, [
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'level_term_id' => $level,
                'fee_category_id' => $feeCategory,
                'ssc_min' => $row->ssc_min, 'ssc_max' => $row->ssc_max,
                'hsc_min' => $row->hsc_min, 'hsc_max' => $row->hsc_max,
                'require_ssc_golden' => (bool) $row->ssc_golden,
                'require_hsc_golden' => (bool) $row->hsc_golden,
                'ug_cgpa_min' => $international ? $row->international_undergraduate_min : $row->undergraduate_min,
                'ug_cgpa_max' => $international ? $row->international_undergraduate_max : $row->undergraduate_max,
                'is_international' => $international ?: null,
                'waiver_percent' => (float) $row->waiver_rate,
                'improved_waiver_percent' => $improved ? (float) $row->max_waiver_rate : null,
                'message' => Normalizer::en(Normalizer::text($row->message)),
                'is_active' => (string) $row->is_active === '1',
            ], existing: $this->same(WaiverRule::class, ['level_term_id' => $level, 'fee_category_id' => $feeCategory, 'name->en' => Normalizer::text($row->name), 'waiver_percent' => (float) $row->waiver_rate]));

            $rule->boards()->sync(collect($boards[$row->id] ?? [])->map(fn (int $id) => $this->ids->find('boards', $id))->filter()->values()->all());
            $rule->tuitions()->sync(collect($tuitions[$row->id] ?? [])->map(fn (int $id) => $this->ids->find('tuitions', $id))->filter()->values()->all());
            $rule->syncGenders(collect($genders[$row->id] ?? [])->map(fn (int $id) => self::GENDERS[$id] ?? null)->filter()->values()->all());
            $rule->syncCategories(collect($cats)->map(fn (int $id) => $this->ids->find('waiver_categories', $id))->filter()->values()->all());
        }
    }

    protected function levelId(string $name): int
    {
        $vocabulary = app(Vocabularies::class)->find('program.level');

        return Term::query()->where('vocabulary_id', $vocabulary->id)->get()
            ->first(fn ($t): bool => $t->getTranslation('name', 'en') === $name)?->id
            ?? throw new \RuntimeException("Program level \"{$name}\" is missing: run the Program module seeder.");
    }

    /** One fee of a legacy column as a line; an empty value removes the line made by an earlier run. */
    protected function line(Tuition $tuition, object $row, string $part, string $type, string $label, mixed $amount): void
    {
        $table = "tuitions.{$part}";
        $value = $this->money($amount);

        if ($value === null || $value <= 0) {
            if ($line = $this->ids->record($table, $row->id, TuitionLine::class)) {
                $line->forceDelete();
                $this->ids->forget($table, $row->id);
            }

            return;
        }

        $this->upsert($table, $row, TuitionLine::class, [
            'tuition_id' => $tuition->id,
            'line_type' => $type,
            'label' => ['en' => $label],
            'amount' => $value,
            'sort_order' => array_search($part, ['admission', 'first_year', 'second_year', 'third_year', 'fourth_year', 'semester', 'other'], true) + 1,
        ], existing: $this->same(TuitionLine::class, ['tuition_id' => $tuition->id, 'line_type' => $type, 'label->en' => $label]));
    }

    /** "952,500" → 952500.0; null / "" → null */
    protected function money(mixed $value): ?float
    {
        $value = trim(str_replace(',', '', (string) $value));

        return is_numeric($value) ? (float) $value : null;
    }

    /** @return array{0: ?float, 1: ?float} "154.5" → [154.5, null]; "40-60" → [40, 60] */
    protected function credits(?string $value): array
    {
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:-|–)\s*(\d+(?:\.\d+)?)\s*$/', (string) $value, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        return [is_numeric(trim((string) $value)) ? (float) $value : null, null];
    }
}
