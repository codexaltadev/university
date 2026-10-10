<?php

namespace Modules\Waiver\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Program\Models\Tuition;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;

/**
 * The Waiver Calculator (FR-FEE-06/07, DB-03 §11.2 v1.1). Same results as DIU's legacy
 * calculator (golden master, Test Plan §7.1.1), with its fixed ids turned into data:
 *
 *  1. A rule applies when it is active, of the level and fee category, and each of its
 *     conditions holds — boards, genders, faculties, tuitions, waiver categories (none
 *     chosen = all; the chosen category also brings the categories it includes), and the
 *     result ranges it has: SSC / HSC GPA with golden where required, or undergraduate
 *     CGPA. A rule without a range does not look at that result.
 *  2. "highest" (default): the best rule by priority, waiver %, improved %; on a tie the
 *     rule of an included category (Result Based) before the chosen quota's own, then the
 *     older rule; then its
 *     improved % counts when the HSC GPA is above the SSC GPA. "additive": the matching
 *     percents add up to the smallest cap of them (or 100).
 *  3. The waiver applies to the tuition only: waiver = tuition × % / 100;
 *     after waiver = total − waiver.
 *
 * Nothing the visitor enters is stored (BRL-FEE-03).
 */
class WaiverCalculator
{
    /**
     * @param  array{level_id: int, fee_category_id: int, tuition_id: int, waiver_category_id: int, board_id: ?int, gender: ?string, ssc?: ?float, ssc_golden?: bool, hsc?: ?float, hsc_golden?: bool, ug_cgpa?: ?float}  $input
     */
    public function calculate(array $input): WaiverResult
    {
        $tuition = Tuition::query()->with(['program.department', 'category', 'lines'])->find($input['tuition_id']);
        $category = WaiverCategory::query()->find($input['waiver_category_id']);

        if ($tuition === null || $category === null) {
            return new WaiverResult(WaiverResult::NO_WAIVER, $tuition);
        }

        $rules = $this->matching($input, $tuition, $category);

        if ($rules->isEmpty()) {
            return new WaiverResult(WaiverResult::NO_WAIVER, $tuition);
        }

        $percent = $this->percent($rules, $input);
        $best = $rules->first();

        $tuitionMin = (float) $tuition->tuition_min;
        if (! ((int) $tuitionMin > 0)) {
            return new WaiverResult(WaiverResult::NO_TUITION, $tuition, $best, $percent, $this->categoryName($best), rules: $rules->all());
        }

        $totalMin = (float) $tuition->total_min;
        $waiver = $tuitionMin / 100 * $percent;
        $min = [
            'total' => $totalMin,
            'tuition' => $tuitionMin,
            'other' => $tuition->otherFees(),
            'admission' => $tuition->admissionFee(),
            'waiver' => $waiver,
            'final' => $totalMin - $waiver,
        ];

        $max = null;
        if ($tuition->total_max !== null && (float) $tuition->total_max > 0 && $totalMin > 0 && (float) $tuition->credit_max > 0) {
            $totalMax = (float) $tuition->total_max;
            $tuitionMax = (float) ($tuition->tuition_max ?? $tuition->tuition_min);
            $max = [
                'total' => $totalMax,
                'tuition' => $tuitionMax,
                'other' => $totalMax - $tuitionMax,
                'waiver' => $tuitionMax / 100 * $percent,
                'final' => $totalMax - $tuitionMax / 100 * $percent,
            ];
        }

        return new WaiverResult(WaiverResult::OK, $tuition, $best, $percent, $this->categoryName($best), $min, $max, $rules->all());
    }

    /**
     * Which results the form asks for at a level and fee category, from the active rules:
     * SSC / HSC GPA, undergraduate CGPA, or both.
     *
     * @return array{school: bool, cgpa: bool}
     */
    public function fields(int $levelId, int $feeCategoryId): array
    {
        $rules = $this->rulesOf($levelId, $feeCategoryId);

        return [
            'school' => (clone $rules)->where(fn (Builder $q) => $q->whereNotNull('ssc_min')->orWhereNotNull('hsc_min'))->exists(),
            'cgpa' => (clone $rules)->whereNotNull('ug_cgpa_min')->exists(),
        ];
    }

    /** Active rules of a level and fee category. */
    public function rulesOf(int $levelId, ?int $feeCategoryId): Builder
    {
        return WaiverRule::query()->frontendVisible()
            ->where('level_term_id', $levelId)
            ->where(fn (Builder $q) => $q->whereNull('fee_category_id')->when($feeCategoryId, fn ($q) => $q->orWhere('fee_category_id', $feeCategoryId)));
    }

    /** @return Collection<int, WaiverRule> best first */
    protected function matching(array $input, Tuition $tuition, WaiverCategory $category): Collection
    {
        $ssc = $input['ssc'] ?? null;
        $hsc = $input['hsc'] ?? null;
        $cgpa = $input['ug_cgpa'] ?? null;
        $facultyId = $tuition->program?->department?->faculty_id;
        $included = array_values(array_map('intval', array_diff($category->matchingIds(), [$category->getKey()])));
        $either = fn (string $relation, \Closure $match) => fn (Builder $q) => $q->doesntHave($relation)->orWhereHas($relation, $match);

        return $this->rulesOf($input['level_id'], $input['fee_category_id'])
            ->where($either('categories', fn (Builder $c) => $c->whereIn('waiver_categories.id', $category->matchingIds())))
            ->where($either('tuitions', fn (Builder $t) => $t->whereKey($tuition->id)))
            ->where($either('boards', fn (Builder $b) => $b->whereKey($input['board_id'] ?? 0)))
            ->where($either('faculties', fn (Builder $f) => $f->whereKey($facultyId ?? 0)))
            ->where(fn (Builder $q) => $q->whereNotExists(fn ($g) => $g->from('waiver_rule_genders')->whereColumn('rule_id', 'waiver_rules.id'))
                ->orWhereExists(fn ($g) => $g->from('waiver_rule_genders')->whereColumn('rule_id', 'waiver_rules.id')->where('gender', $input['gender'] ?? '')))
            ->where(fn (Builder $q) => $q->whereNull('ssc_min')->orWhere(fn ($r) => $r->where('ssc_min', '<=', $ssc ?? -1)->where('ssc_max', '>=', $ssc ?? -1)))
            ->where(fn (Builder $q) => $q->whereNull('hsc_min')->orWhere(fn ($r) => $r->where('hsc_min', '<=', $hsc ?? -1)->where('hsc_max', '>=', $hsc ?? -1)))
            ->where(fn (Builder $q) => $q->whereNull('ug_cgpa_min')->orWhere(fn ($r) => $r->where('ug_cgpa_min', '<=', $cgpa ?? -1)->where('ug_cgpa_max', '>=', $cgpa ?? -1)))
            ->when(! ($input['ssc_golden'] ?? false), fn (Builder $q) => $q->where('require_ssc_golden', false))
            ->when(! ($input['hsc_golden'] ?? false), fn (Builder $q) => $q->where('require_hsc_golden', false))
            ->orderByDesc('priority')
            ->orderByDesc('waiver_percent')
            ->orderByRaw('improved_waiver_percent IS NULL, improved_waiver_percent DESC')
            // On a tie the rule of an included category (Result Based) wins over the chosen quota's own,
            // as in the legacy calculator (golden master): a quota names the result only when it gives more.
            ->when($included !== [], fn (Builder $q) => $q->orderByRaw(
                'EXISTS (SELECT 1 FROM waiver_rule_categories rc WHERE rc.rule_id = waiver_rules.id AND rc.waiver_category_id IN ('.implode(',', $included).')) DESC'))
            ->orderBy('id')
            ->get();
    }

    /** FR-FEE-07 */
    protected function percent(Collection $rules, array $input): float
    {
        $improved = fn (WaiverRule $rule): float => $rule->improved_waiver_percent !== null && ($input['hsc'] ?? 0) > ($input['ssc'] ?? 0)
            ? max((float) $rule->waiver_percent, (float) $rule->improved_waiver_percent)
            : (float) $rule->waiver_percent;

        if (settings('waiver.waiver_mode', 'highest') !== 'additive') {
            return $improved($rules->first());
        }

        $cap = $rules->pluck('max_waiver_percent')->filter(fn ($v) => $v !== null)->map(fn ($v): float => (float) $v)->min() ?? 100.0;

        return min($cap, (float) $rules->sum(fn (WaiverRule $rule): float => $improved($rule)));
    }

    protected function categoryName(WaiverRule $rule): ?string
    {
        return $rule->categories->first()?->name;
    }
}
