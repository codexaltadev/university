<?php

/*
| Phase 3.4b — waiver rules and the Waiver Calculator (FR-FEE-05..09, AC-FEE-01/02,
| BRL-FEE-01..04, DB-03 §11.2 v1.1). The full legacy comparison is the golden master
| (tests/Golden, sample in tests/Feature/Legacy); these cases pin each rule by hand.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Models\TuitionLine;
use Modules\Waiver\Filament\Resources\Boards\BoardResource;
use Modules\Waiver\Filament\Resources\WaiverCategories\WaiverCategoryResource;
use Modules\Waiver\Filament\Resources\WaiverRules\Pages\CreateWaiverRule;
use Modules\Waiver\Filament\Resources\WaiverRules\Pages\EditWaiverRule;
use Modules\Waiver\Filament\Resources\WaiverRules\Pages\ListWaiverRules;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;
use Modules\Waiver\Livewire\WaiverCalculatorForm;
use Modules\Waiver\Models\Board;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Modules\Waiver\Support\CalculatorForm;
use Modules\Waiver\Support\WaiverCalculator;
use Modules\Waiver\Support\WaiverResult;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Tuition', 'Waiver'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->ug = ProgramFactory::level('Undergraduate');
    $this->grad = ProgramFactory::level('Graduate');
    $this->local = FeeCategory::query()->create(['name' => ['en' => 'Local Tuition Fee'], 'code' => 'local', 'currency' => 'BDT']);
    $this->intl = FeeCategory::query()->create(['name' => ['en' => 'International Tuition Fee'], 'code' => 'international', 'currency' => 'USD']);

    $faculty = Faculty::factory()->create(['short_name' => 'FSIT']);
    $dept = Department::factory()->create(['faculty_id' => $faculty->id, 'short_name' => 'SWE']);
    $this->bsc = Program::factory()->create(['department_id' => $dept->id, 'level_term_id' => $this->ug, 'name' => ['en' => 'B. Sc. in Software Engineering']]);
    $this->msc = Program::factory()->create(['department_id' => $dept->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'M. Sc. in Software Engineering']]);

    // DIU figures: tuition 709,500 of a 952,500 total; admission 61,750
    $this->tuition = Tuition::query()->create(['program_id' => $this->bsc->id, 'fee_category_id' => $this->local->id, 'credit_min' => 147,
        'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500]);
    TuitionLine::query()->create(['tuition_id' => $this->tuition->id, 'line_type' => TuitionLine::ADMISSION, 'label' => ['en' => 'Admission fee'], 'amount' => 61750]);
    $this->mscTuition = Tuition::query()->create(['program_id' => $this->msc->id, 'fee_category_id' => $this->local->id, 'credit_min' => 42, 'credit_max' => 63,
        'totals_overridden' => true, 'tuition_min' => 99000, 'tuition_max' => 162000, 'total_min' => 173500, 'total_max' => 249000]);

    $this->general = Board::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'General Education Board']]);
    $this->madrasha = Board::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'Madrasha Board']]);
    $this->result = WaiverCategory::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'Result Based Waiver(SSC & HSC)']]);
    $this->female = WaiverCategory::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'Female']]);
    $this->female->includes()->sync([$this->result->id]);
    $this->college = WaiverCategory::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'Daffodil International College']]);
    $this->college->includes()->sync([$this->result->id]);
    $this->diploma = WaiverCategory::query()->create(['level_term_id' => $this->ug, 'name' => ['en' => 'Diploma Holders'], 'uses_diploma_result' => true]);

    $this->rule = function (array $attributes, array $categories, array $boards = [], array $genders = [], array $tuitions = []): WaiverRule {
        $rule = WaiverRule::query()->create(['name' => ['en' => 'Rule'], 'level_term_id' => $this->ug, 'fee_category_id' => $this->local->id, ...$attributes]);
        $rule->syncCategories(array_map(fn ($c) => $c->id, $categories));
        $rule->boards()->sync(array_map(fn ($b) => $b->id, $boards));
        $rule->syncGenders($genders);
        $rule->tuitions()->sync(array_map(fn ($t) => $t->id, $tuitions));

        return $rule;
    };
    $this->ask = fn (array $input): WaiverResult => app(WaiverCalculator::class)->calculate([
        'level_id' => $this->ug, 'fee_category_id' => $this->local->id, 'tuition_id' => $this->tuition->id, 'waiver_category_id' => $this->result->id,
        'board_id' => $this->general->id, 'gender' => 'male', ...$input,
    ]);

    // DIU-like result rules
    ($this->rule)(['name' => ['en' => 'HSC 4.90–4.99'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 4.9, 'hsc_max' => 4.99, 'waiver_percent' => 15, 'message' => ['en' => 'Keep SGPA 3.00.']], [$this->result]);
    ($this->rule)(['name' => ['en' => 'GPA 5 (not golden)'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 20], [$this->result]);
    ($this->rule)(['name' => ['en' => 'Golden both'], 'ssc_min' => 5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'require_ssc_golden' => true, 'require_hsc_golden' => true, 'waiver_percent' => 50], [$this->result], [$this->general]);
});

it('gives the waiver and the cost after it (AC-FEE-01, FR-FEE-06)', function () {
    $answer = ($this->ask)(['ssc' => 5.0, 'hsc' => 5.0]);   // GPA 5, not golden

    expect($answer->status)->toBe(WaiverResult::OK)
        ->and($answer->percent)->toBe(20.0)
        ->and($answer->categoryName)->toBe('Result Based Waiver(SSC & HSC)')
        ->and($answer->min)->toBe(['total' => 952500.0, 'tuition' => 709500.0, 'other' => 243000.0, 'admission' => 61750.0, 'waiver' => 141900.0, 'final' => 810600.0])
        ->and($answer->max)->toBeNull();
});

it('says when no rule applies (AC-FEE-02), and limits each condition', function () {
    expect(($this->ask)(['ssc' => 4.0, 'hsc' => 4.0])->status)->toBe(WaiverResult::NO_WAIVER)
        ->and(($this->ask)(['ssc' => 4.0, 'hsc' => 4.89])->status)->toBe(WaiverResult::NO_WAIVER)   // just below 4.90
        ->and(($this->ask)(['ssc' => 4.0, 'hsc' => 4.9])->percent)->toBe(15.0);

    // Golden needs both golden — and the board the rule names; others fall back to GPA 5
    expect(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0, 'ssc_golden' => true, 'hsc_golden' => true])->percent)->toBe(50.0)
        ->and(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0, 'hsc_golden' => true])->percent)->toBe(20.0)
        ->and(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0, 'ssc_golden' => true, 'hsc_golden' => true, 'board_id' => $this->madrasha->id])->percent)->toBe(20.0);

    // Gender and tuition conditions
    ($this->rule)(['name' => ['en' => 'Female only'], 'hsc_min' => 4.0, 'hsc_max' => 4.49, 'ssc_min' => 2.5, 'ssc_max' => 5, 'waiver_percent' => 10], [$this->result], genders: ['female'], tuitions: [$this->tuition]);
    expect(($this->ask)(['ssc' => 4.0, 'hsc' => 4.2])->status)->toBe(WaiverResult::NO_WAIVER)
        ->and(($this->ask)(['ssc' => 4.0, 'hsc' => 4.2, 'gender' => 'female'])->percent)->toBe(10.0);
});

it('lets a category also match the rules of the categories it includes; on a tie the included one names the result', function () {
    ($this->rule)(['name' => ['en' => 'Female 4.00–4.49'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 4.0, 'hsc_max' => 4.49, 'waiver_percent' => 10], [$this->female, $this->result]);
    ($this->rule)(['name' => ['en' => 'Female GPA 5'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 20], [$this->female]);

    $female = ($this->ask)(['ssc' => 5.0, 'hsc' => 5.0, 'waiver_category_id' => $this->female->id, 'gender' => 'female']);
    expect($female->percent)->toBe(20.0)
        ->and($female->categoryName)->toBe('Result Based Waiver(SSC & HSC)')   // two 20% rules: the included category's wins (as DIU's)
        ->and(count($female->rules))->toBe(2);

    // The quota names the result when it gives more
    ($this->rule)(['name' => ['en' => 'Female GPA 5, more'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 25], [$this->female]);
    expect(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0, 'waiver_category_id' => $this->female->id])->categoryName)->toBe('Female');

    // A rule of two categories applies to both; the result names its first category (as DIU's)
    expect(($this->ask)(['ssc' => 4.0, 'hsc' => 4.2])->categoryName)->toBe('Female')
        ->and(($this->ask)(['ssc' => 4.0, 'hsc' => 4.2, 'waiver_category_id' => $this->female->id])->percent)->toBe(10.0);

    // "Result Based" does not include "Female": the Female-only rules are not its
    expect(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0])->percent)->toBe(20.0);
});

it('gives the improved percent when the HSC GPA beats the SSC GPA (DB-03 v1.1 #2)', function () {
    ($this->rule)(['name' => ['en' => 'College quota'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 2.5, 'hsc_max' => 5, 'waiver_percent' => 20, 'improved_waiver_percent' => 30], [$this->college]);
    $ask = fn (float $ssc, float $hsc) => ($this->ask)(['ssc' => $ssc, 'hsc' => $hsc, 'waiver_category_id' => $this->college->id])->percent;

    expect($ask(4.0, 4.5))->toBe(30.0)
        ->and($ask(4.5, 4.0))->toBe(20.0)
        ->and($ask(4.5, 4.5))->toBe(20.0)
        ->and($ask(4.0, 5.0))->toBe(30.0);   // a 20% tie with the GPA 5 result rule: the one with an improved % comes first
});

it('reads a diploma result in place of HSC, and adds up in additive mode (FR-FEE-07)', function () {
    ($this->rule)(['name' => ['en' => 'Diploma 3.90–4.00'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 3.9, 'hsc_max' => 4.0, 'waiver_percent' => 50], [$this->diploma]);
    expect(($this->ask)(['ssc' => 4.0, 'hsc' => 3.95, 'waiver_category_id' => $this->diploma->id])->percent)->toBe(50.0);

    ($this->rule)(['name' => ['en' => 'Extra 10, cap 25'], 'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 10, 'max_waiver_percent' => 25], [$this->result]);
    settings()->save('waiver', ['waiver_mode' => 'additive']);
    expect(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0])->percent)->toBe(25.0);   // 20 + 10, capped at 25
});

it('shows ranges for programs whose credits vary, and refuses tuitions without a tuition amount', function () {
    $grad = Board::query()->create(['level_term_id' => $this->grad, 'name' => ['en' => 'Public University']]);
    $gradResult = WaiverCategory::query()->create(['level_term_id' => $this->grad, 'name' => ['en' => 'Result Based']]);
    $rule = WaiverRule::query()->create(['name' => ['en' => 'CGPA 3.50–3.89'], 'level_term_id' => $this->grad, 'fee_category_id' => $this->local->id, 'ug_cgpa_min' => 3.5, 'ug_cgpa_max' => 3.89, 'waiver_percent' => 30]);
    $rule->syncCategories([$gradResult->id]);

    $answer = app(WaiverCalculator::class)->calculate(['level_id' => $this->grad, 'fee_category_id' => $this->local->id, 'tuition_id' => $this->mscTuition->id,
        'waiver_category_id' => $gradResult->id, 'board_id' => $grad->id, 'gender' => 'female', 'ug_cgpa' => 3.6]);
    expect($answer->percent)->toBe(30.0)
        ->and($answer->min['final'])->toBe(173500.0 - 29700.0)
        ->and($answer->max)->toBe(['total' => 249000.0, 'tuition' => 162000.0, 'other' => 87000.0, 'waiver' => 48600.0, 'final' => 200400.0]);

    $this->tuition->update(['tuition_min' => null]);
    expect(($this->ask)(['ssc' => 5.0, 'hsc' => 5.0])->status)->toBe(WaiverResult::NO_TUITION);
});

it('creates a rule with ordered categories and genders, and tests rules on the admin screen (FR-FEE-05/09)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateWaiverRule::class)
        ->fillForm(['name' => ['en' => 'Female GPA 5'], 'level_term_id' => $this->ug, 'fee_category_id' => $this->local->id,
            'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 25,
            'waiver_category_ids' => [$this->female->id, $this->result->id], 'genders' => ['female'], 'boards' => [$this->general->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    $rule = WaiverRule::query()->latest('id')->first();
    expect($rule->categories->pluck('id')->all())->toBe([$this->female->id, $this->result->id])
        ->and($rule->genders())->toBe(['female'])
        ->and($rule->boards->pluck('id')->all())->toBe([$this->general->id]);

    // BRL-FEE-01: max below min, GPA above 5
    Livewire::test(EditWaiverRule::class, ['record' => $rule->getRouteKey()])
        ->assertFormSet(['waiver_category_ids' => [$this->female->id, $this->result->id], 'genders' => ['female']])
        ->fillForm(['hsc_min' => 4.5, 'hsc_max' => 4.0, 'ssc_max' => 5.5])
        ->call('save')
        ->assertHasFormErrors(['hsc_max', 'ssc_max']);

    Livewire::test(ListWaiverRules::class)
        ->callAction('test', data: ['fee_category_id' => $this->local->id, 'level_id' => $this->ug, 'tuition_id' => $this->tuition->id,
            'waiver_category_id' => $this->female->id, 'board_id' => $this->general->id, 'gender' => 'female', 'ssc' => 5, 'hsc' => 5])
        ->assertNotified();
});

it('runs the calculator on /waiver-calculator from the data, storing nothing (FR-FEE-06/08, BRL-FEE-03)', function () {
    settings()->save('waiver', ['disclaimer' => ['en' => 'Figures are estimates; the Admission Office decides.']], translatable: ['disclaimer']);

    $this->get('/waiver-calculator')->assertOk()->assertSee('Waiver Calculator')->assertSeeLivewire(WaiverCalculatorForm::class)
        ->assertSeeInOrder(['Calculate Your Fees', 'Important Notice', 'Figures are estimates; the Admission Office decides.', '/scholarships', 'View Details']);   // the default theme: DIU's hero and notice, from Settings

    $rows = DB::table('waiver_rules')->count();
    Livewire::test(WaiverCalculatorForm::class)
        ->assertSet('feeCategoryId', $this->local->id)                 // the international one has no rules
        ->assertSet('levelId', $this->ug)
        ->assertSee('B. Sc. in Software Engineering')
        ->assertSee('General Education Board')
        ->call('calculate')
        ->assertHasErrors(['tuitionId', 'waiverCategoryId', 'gender', 'ssc', 'hsc'])
        ->set('tuitionId', $this->tuition->id)
        ->set('waiverCategoryId', $this->result->id)
        ->set('boardId', $this->general->id)
        ->set('gender', 'male')
        ->set('ssc', '5')->set('sscGolden', true)
        ->set('hsc', '5.5')
        ->call('calculate')
        ->assertHasErrors(['hsc'])                                    // BRL-FEE-01
        ->set('hsc', '5')->set('hscGolden', true)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('50%')
        ->assertSee('৳ 597,750')                                       // 952,500 − 50% of 709,500
        ->assertSee('৳ 61,750')
        ->assertSeeInOrder(['Congratulations!', '50%', 'Fee Summary', 'Payable During Admission', 'After waiver Total Cost will be'])   // DIU's result panel
        ->set('hsc', '4.95')
        ->assertSet('hscGolden', false)                               // golden only at GPA 5
        ->assertSet('answer', null)
        ->set('hsc', '5')->call('calculate')
        ->call('clear')                                               // DIU's "Reset"
        ->assertSet('answer', null)->assertSet('tuitionId', null)->assertSet('ssc', null)
        ->assertSet('feeCategoryId', $this->local->id);

    expect(DB::table('waiver_rules')->count())->toBe($rows);

    // A diploma category asks the Diploma CGPA (out of 4)
    Livewire::test(WaiverCalculatorForm::class)->set('waiverCategoryId', $this->diploma->id)->assertSee('Diploma CGPA (out of 4)');
});

it('keeps the rules in Academic › Waiver Calculator, not in the Program Hub', function () {
    app(ModulePermissions::class)->sync('Program');
    $this->actingAs(makeUser(['ViewAny:Program', 'View:Program', 'ViewAny:WaiverRule', 'View:WaiverRule', 'ViewAny:WaiverCategory', 'ViewAny:Board']));

    $this->get('/admin/waiver-calculator')->assertRedirect(WaiverRuleResource::getUrl('index'));
    $this->get(WaiverRuleResource::getUrl('index'))->assertOk()
        ->assertSee('/admin/waiver-calculator/waiver-rules', false)
        ->assertSee(WaiverCategoryResource::getUrl('index'), false)
        ->assertSee(BoardResource::getUrl('index'), false);

    $this->get(ProgramResource::getUrl('view', ['record' => $this->bsc]))->assertOk()
        ->assertDontSee('waiver-rules', false);
});

it('gives each waiver category DIU\'s tile icon by its name, and no notice without a disclaimer', function () {
    expect(CalculatorForm::icon('Result Based Waiver (SSC & HSC)'))->toBe('award')
        ->and(CalculatorForm::icon('Sibling / Spouse'))->toBe('users')
        ->and(CalculatorForm::icon('Female'))->toBe('star')
        ->and(CalculatorForm::icon('Physically Challenged'))->toBe('shield')
        ->and(CalculatorForm::icon('Tribal/Ethnic Group'))->toBe('globe')
        ->and(CalculatorForm::icon('Daffodil International College'))->toBe('graduation-cap')
        ->and(CalculatorForm::icon('Diploma Holders'))->toBe('book-open');

    // The short form (owner 2026-10-25): three steps, a category's own description under its name, programs to search
    $this->result->update(['description' => ['en' => 'By SSC and HSC GPA']]);
    Livewire::test(WaiverCalculatorForm::class)
        ->assertSeeInOrder(['Choose your program', 'Choose your waiver category', 'By SSC and HSC GPA', 'Your results', 'Your fee estimate'])
        ->assertSee('B. Sc. in Software Engineering')
        ->assertDontSee('Waiver category for');

    settings()->save('waiver', ['disclaimer' => ['en' => '']], translatable: ['disclaimer']);
    $this->get('/waiver-calculator')->assertOk()->assertDontSee('Important Notice');
});
