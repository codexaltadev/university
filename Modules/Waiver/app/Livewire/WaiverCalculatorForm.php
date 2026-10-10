<?php

namespace Modules\Waiver\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Waiver\Models\Board;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Modules\Waiver\Support\WaiverCalculator;
use Platform\Taxonomy\Term;

/**
 * The Waiver Calculator form on /waiver-calculator (FR-FEE-06/08). Every list comes
 * from the data: fee categories and levels that have active rules, the visible
 * programs' tuitions, the level's waiver categories and the boards its rules use;
 * the results asked for follow the rules too (SSC / HSC GPA or undergraduate CGPA;
 * a diploma category asks the Diploma CGPA in place of HSC). Nothing is stored (BRL-FEE-03).
 * The view is theme-overridable: modules/fee/livewire/waiver-calculator.
 */
class WaiverCalculatorForm extends Component
{
    public ?int $feeCategoryId = null;

    public ?int $levelId = null;

    public ?int $tuitionId = null;

    public ?int $waiverCategoryId = null;

    public ?int $boardId = null;

    public ?string $gender = null;

    public ?string $ssc = null;

    public bool $sscGolden = false;

    public ?string $hsc = null;

    public bool $hscGolden = false;

    public ?string $cgpa = null;

    public ?array $answer = null;

    public function mount(): void
    {
        $this->feeCategoryId = $this->feeCategories()->first()?->id;
        $this->levelId = $this->levels()->first()?->id;
    }

    /** A changed choice clears what depends on it. */
    public function updated(string $property): void
    {
        $this->answer = null;

        if (in_array($property, ['feeCategoryId', 'levelId'], true)) {
            if ($property === 'feeCategoryId' && ! $this->levels()->contains('id', $this->levelId)) {
                $this->levelId = $this->levels()->first()?->id;
            }
            $this->tuitionId = $this->waiverCategoryId = $this->boardId = null;
        }

        if ($property === 'ssc' && (float) $this->ssc !== 5.0) {
            $this->sscGolden = false;
        }
        if ($property === 'hsc' && (float) $this->hsc !== 5.0) {
            $this->hscGolden = false;
        }
    }

    public function calculate(WaiverCalculator $calculator): void
    {
        $fields = $this->fields();
        $diploma = $this->waiverCategory()?->uses_diploma_result ?? false;

        $this->validate(array_filter([
            'feeCategoryId' => ['required', 'integer'],
            'levelId' => ['required', 'integer'],
            'tuitionId' => ['required', 'integer', 'in:'.$this->tuitions()->pluck('id')->implode(',')],
            'waiverCategoryId' => ['required', 'integer', 'in:'.$this->waiverCategories()->pluck('id')->implode(',')],
            'boardId' => $this->boards()->isEmpty() ? null : ['required', 'integer', 'in:'.$this->boards()->pluck('id')->implode(',')],
            'gender' => ['required', 'in:'.implode(',', WaiverRule::GENDERS)],
            'ssc' => $fields['school'] ? ['required', 'numeric', 'min:0', 'max:5'] : null,      // BRL-FEE-01
            'hsc' => $fields['school'] ? ['required', 'numeric', 'min:0', 'max:'.($diploma ? 4 : 5)] : null,
            'cgpa' => $fields['cgpa'] ? ['required', 'numeric', 'min:0', 'max:4'] : null,
        ]), attributes: [
            'ssc' => __('waiver::waiver.calculator.ssc'), 'hsc' => $diploma ? __('waiver::waiver.calculator.diploma') : __('waiver::waiver.calculator.hsc'),
            'cgpa' => __('waiver::waiver.calculator.cgpa'), 'tuitionId' => __('waiver::waiver.fields.program'), 'waiverCategoryId' => __('waiver::waiver.calculator.waiver_category'),
            'boardId' => __('waiver::waiver.calculator.board'), 'gender' => __('waiver::waiver.calculator.gender'),
        ]);

        $result = $calculator->calculate([
            'level_id' => $this->levelId,
            'fee_category_id' => $this->feeCategoryId,
            'tuition_id' => $this->tuitionId,
            'waiver_category_id' => $this->waiverCategoryId,
            'board_id' => $this->boardId,
            'gender' => $this->gender,
            'ssc' => $fields['school'] ? (float) $this->ssc : null,
            'ssc_golden' => $fields['school'] && $this->sscGolden && (float) $this->ssc === 5.0,
            'hsc' => $fields['school'] ? (float) $this->hsc : null,
            'hsc_golden' => $fields['school'] && ! $diploma && $this->hscGolden && (float) $this->hsc === 5.0,
            'ug_cgpa' => $fields['cgpa'] ? (float) $this->cgpa : null,
        ]);

        $this->answer = ['status' => $result->status, 'percent' => $result->percent, 'category' => $result->categoryName,
            'tuition_id' => $result->tuition?->id, 'rule_id' => $result->rule?->id, 'min' => $result->min, 'max' => $result->max];
    }

    public function render(): View
    {
        $tuition = $this->answer ? Tuition::query()->with(['program', 'category'])->find($this->answer['tuition_id']) : null;

        return view('waiver::livewire.waiver-calculator', [
            'feeCategories' => $this->feeCategories(),
            'levels' => $this->levels(),
            'tuitions' => $this->tuitions(),
            'waiverCategories' => $this->waiverCategories(),
            'boards' => $this->boards(),
            'genders' => $this->genders(),
            'fields' => $this->fields(),
            'diploma' => $this->waiverCategory()?->uses_diploma_result ?? false,
            'tuition' => $tuition,
            'rule' => $this->answer ? WaiverRule::query()->find($this->answer['rule_id']) : null,
            'disclaimer' => $this->disclaimer(),
        ]);
    }

    /** Fee categories with active rules. @return Collection<int, FeeCategory> */
    protected function feeCategories(): Collection
    {
        return FeeCategory::query()->frontendVisible()->ordered()->get()
            ->filter(fn (FeeCategory $c): bool => WaiverRule::query()->frontendVisible()->where(fn (Builder $q) => $q->whereNull('fee_category_id')->orWhere('fee_category_id', $c->id))->exists())
            ->values();
    }

    /** Levels with active rules in the fee category. @return Collection<int, Term> */
    protected function levels(): Collection
    {
        $ids = $this->feeCategoryId ? $this->rules(null)->distinct()->pluck('level_term_id') : collect();

        return Term::query()->whereIn('id', $ids)->ordered()->get();
    }

    /** @return Collection<int, Tuition> */
    protected function tuitions(): Collection
    {
        if (! $this->feeCategoryId || ! $this->levelId) {
            return collect();
        }

        return Tuition::query()->visible()->where('fee_category_id', $this->feeCategoryId)
            ->whereHas('program', fn (Builder $p) => $p->where('level_term_id', $this->levelId))
            ->with('program')->ordered()->get();
    }

    /** @return Collection<int, WaiverCategory> */
    protected function waiverCategories(): Collection
    {
        return $this->levelId ? WaiverCategory::query()->frontendVisible()->where('level_term_id', $this->levelId)->ordered()->get() : collect();
    }

    /** Boards the level's rules use (all the level's boards when a rule takes any board). @return Collection<int, Board> */
    protected function boards(): Collection
    {
        if (! $this->levelId) {
            return collect();
        }

        $rules = $this->rules($this->levelId);
        $boards = Board::query()->frontendVisible()->where('level_term_id', $this->levelId)->ordered();

        if (! (clone $rules)->doesntHave('boards')->exists()) {
            $boards->whereIn('id', fn ($q) => $q->select('board_id')->from('waiver_rule_boards')->whereIn('rule_id', (clone $rules)->select('id')));
        }

        return $boards->get();
    }

    /** @return list<string> */
    protected function genders(): array
    {
        $used = $this->levelId ? collect(WaiverRule::GENDERS)->filter(fn (string $g): bool => DB::table('waiver_rule_genders')->where('gender', $g)
            ->whereIn('rule_id', $this->rules($this->levelId)->select('id'))->exists())->values()->all() : [];

        return $used === [] ? ['male', 'female'] : $used;
    }

    /** @return array{school: bool, cgpa: bool} */
    protected function fields(): array
    {
        return $this->feeCategoryId && $this->levelId ? app(WaiverCalculator::class)->fields($this->levelId, $this->feeCategoryId) : ['school' => false, 'cgpa' => false];
    }

    protected function rules(?int $levelId): Builder
    {
        return WaiverRule::query()->frontendVisible()
            ->when($levelId, fn (Builder $q) => $q->where('level_term_id', $levelId))
            ->where(fn (Builder $q) => $q->whereNull('fee_category_id')->orWhere('fee_category_id', $this->feeCategoryId));
    }

    protected function waiverCategory(): ?WaiverCategory
    {
        return $this->waiverCategoryId ? WaiverCategory::query()->find($this->waiverCategoryId) : null;
    }

    protected function disclaimer(): ?string
    {
        $text = settings('waiver.disclaimer');

        return is_array($text) ? ($text[app()->getLocale()] ?? $text['en'] ?? null) : $text;
    }
}
