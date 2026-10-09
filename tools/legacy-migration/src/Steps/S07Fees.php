<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\TuitionLine;

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
        return 'Tuition fees';
    }

    public static function requires(): array
    {
        return ['program', 'fee'];
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
