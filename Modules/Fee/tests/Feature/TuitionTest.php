<?php

/*
| Phase 3.4a — Fee categories and tuition fees (M-10): totals worked out from the
| fee lines (AC-FEE-03) or entered by hand, ranges, admin form with lines, scope,
| /tuition-fees with filters (FR-FEE-03), the program page tab (FR-FEE-04).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Fee\Filament\Resources\Tuitions\Pages\CreateTuition;
use Modules\Fee\Filament\Resources\Tuitions\TuitionResource;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\TuitionLine;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Models\Program;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program', 'Fee'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->local = FeeCategory::query()->create(['name' => ['en' => 'Local Tuition Fee'], 'code' => 'local', 'currency' => 'BDT']);
    $this->intl = FeeCategory::query()->create(['name' => ['en' => 'International Tuition Fee'], 'code' => 'international', 'currency' => 'USD']);

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT', 'name' => ['en' => 'Science and Information Technology']]);
    $this->fbe = Faculty::factory()->create(['short_name' => 'FBE', 'name' => ['en' => 'Business & Entrepreneurship']]);
    $this->swe = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'SWE']);
    $this->bba = Department::factory()->create(['faculty_id' => $this->fbe->id, 'short_name' => 'BBA']);
    $this->bsc = Program::factory()->create(['department_id' => $this->swe->id, 'name' => ['en' => 'B. Sc. in Software Engineering'], 'short_name' => 'SWE']);
    $this->mba = Program::factory()->create(['department_id' => $this->bba->id, 'name' => ['en' => 'Master of Business Administration'], 'short_name' => 'MBA', 'level_term_id' => ProgramFactory::level('Graduate')]);
});

function tuitionWith(Program $program, FeeCategory $category, array $lines, array $attributes = []): Tuition
{
    $tuition = Tuition::query()->create(['program_id' => $program->id, 'fee_category_id' => $category->id, ...$attributes]);
    foreach ($lines as $i => [$type, $label, $amount, $max]) {
        TuitionLine::query()->create(['tuition_id' => $tuition->id, 'line_type' => $type, 'label' => ['en' => $label], 'amount' => $amount, 'amount_max' => $max, 'sort_order' => $i]);
    }

    return $tuition->fresh();
}

it('works the totals out from the lines and again when a fee changes (FR-FEE-02, AC-FEE-03)', function () {
    $tuition = tuitionWith($this->bsc, $this->local, [
        [TuitionLine::ADMISSION, 'Admission fee', 61750, null],
        [TuitionLine::PER_CREDIT, 'Per credit', 4000, null],
        [TuitionLine::TERM, 'Lab per semester', 5000, null],
        [TuitionLine::OTHER, 'Other fees', 20000, null],
    ], ['credit_min' => 147, 'total_terms' => 12]);

    // tuition = 4,000 × 147 + 5,000 × 12 = 648,000; total = + 61,750 + 20,000
    expect((float) $tuition->tuition_min)->toBe(648000.0)
        ->and((float) $tuition->total_min)->toBe(729750.0)
        ->and($tuition->tuition_max)->toBeNull();

    $tuition->lines()->where('line_type', TuitionLine::PER_CREDIT)->first()->update(['amount' => 4500]);
    expect((float) $tuition->fresh()->tuition_min)->toBe(721500.0);

    $tuition->update(['credit_min' => 150]);
    expect((float) $tuition->fresh()->tuition_min)->toBe(735000.0);

    $tuition->lines()->where('line_type', TuitionLine::OTHER)->first()->delete();
    expect((float) $tuition->fresh()->total_min)->toBe(796750.0);
});

it('keeps totals entered by hand, and shows ranges (40 – 60 credits)', function () {
    $manual = tuitionWith($this->bsc, $this->local, [[TuitionLine::TERM, 'Per semester', 78000, null]], [
        'credit_min' => 147, 'total_terms' => 12, 'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500,
    ]);
    $manual->lines()->first()->update(['amount' => 90000]);
    expect((float) $manual->fresh()->tuition_min)->toBe(709500.0);   // DIU's figures stay

    $ranged = tuitionWith($this->mba, $this->local, [
        [TuitionLine::ADMISSION, 'Admission fee', 30250, null],
        [TuitionLine::PER_CREDIT, 'Per credit', 3000, 3200],
    ], ['credit_min' => 40, 'credit_max' => 60]);

    expect((float) $ranged->tuition_min)->toBe(120000.0)
        ->and((float) $ranged->tuition_max)->toBe(192000.0)
        ->and($ranged->creditsText())->toBe('40 – 60')
        ->and($ranged->hasRange())->toBeTrue()
        ->and($ranged->admissionFee())->toBe(30250.0)
        ->and($ranged->otherFees())->toBe(30250.0);   // no "other" line: total − tuition, as DIU shows it
});

it('creates a tuition with its fees on the admin form', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateTuition::class)
        ->fillForm([
            'program_id' => $this->bsc->id, 'fee_category_id' => $this->local->id, 'credit_min' => 147, 'total_terms' => 12,
            'duration_label' => ['en' => '4 Years'],
            'lines' => [
                ['line_type' => TuitionLine::ADMISSION, 'label' => ['en' => 'Admission fee'], 'amount' => 61750],
                ['line_type' => TuitionLine::TERM, 'label' => ['en' => 'Per semester'], 'amount' => 59125],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tuition = Tuition::query()->sole();
    expect($tuition->lines)->toHaveCount(2)
        ->and((float) $tuition->tuition_min)->toBe(709500.0)
        ->and((float) $tuition->total_min)->toBe(771250.0);
});

it('lets a program user manage only their program\'s fees', function () {
    $own = tuitionWith($this->bsc, $this->local, []);
    $other = tuitionWith($this->mba, $this->local, []);

    $this->actingAs(makeUser(['ViewAny:Tuition', 'View:Tuition', 'Update:Tuition'], [$this->bsc]));
    $this->get(TuitionResource::getUrl('edit', ['record' => $own]))->assertOk();
    $this->get(TuitionResource::getUrl('edit', ['record' => $other]))->assertNotFound();

    // A Faculty user reaches the fees of its programs through the hierarchy
    $this->actingAs(makeUser(['ViewAny:Tuition', 'View:Tuition'], [$this->fbe]));
    $this->get(TuitionResource::getUrl())->assertOk()->assertSee('Master of Business Administration')->assertDontSee('B. Sc. in Software Engineering');
});

it('lists /tuition-fees by category, grouped by faculty, with filters (FR-FEE-03)', function () {
    tuitionWith($this->bsc, $this->local, [[TuitionLine::ADMISSION, 'Admission fee', 61750, null]], ['totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500]);
    tuitionWith($this->mba, $this->local, [], ['totals_overridden' => true, 'tuition_min' => 131400, 'tuition_max' => 219000, 'total_min' => 189025, 'total_max' => 315000]);
    tuitionWith($this->bsc, $this->intl, [], ['totals_overridden' => true, 'tuition_min' => 7271, 'total_min' => 9726]);

    $this->get('/tuition-fees')->assertOk()
        ->assertSeeInOrder(['Science and Information Technology', 'B. Sc. in Software Engineering', '৳ 952,500', 'Business &amp; Entrepreneurship', '৳ 189,025 – ৳ 315,000'], false)
        ->assertDontSee('$ 9,726');

    $this->get('/tuition-fees?category=international')->assertOk()->assertSee('$ 9,726')->assertDontSee('৳ 952,500');
    $this->get('/tuition-fees?level=graduate')->assertSee('Master of Business Administration')->assertDontSee('B. Sc. in Software Engineering');
    $this->get('/tuition-fees?faculty=fsit')->assertSee('B. Sc. in Software Engineering')->assertDontSee('Master of Business Administration');

    // An inactive program takes its fees off the page (BRL-PROG-03)
    $this->mba->update(['is_active' => false]);
    $this->get('/tuition-fees')->assertDontSee('৳ 189,025', false);
});

it('adds a "Tuition Fees" tab to the program page, only where fees exist (FR-FEE-04, FR-PROG-18)', function () {
    tuitionWith($this->bsc, $this->local, [[TuitionLine::ADMISSION, 'Admission fee', 61750, null], [TuitionLine::TERM, 'Per semester', 78000, null]], [
        'credit_min' => 147, 'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500, 'prospectus_url' => 'https://example.edu/prospectus.pdf',
    ]);

    $this->get($this->bsc->url())->assertOk()
        ->assertSee('href="#fee-tuition"', false)
        ->assertSee('Local Tuition Fee')->assertSee('৳ 61,750')->assertSee('৳ 78,000')->assertSee('৳ 952,500')
        ->assertSee('https://example.edu/prospectus.pdf', false);

    $this->get($this->mba->url())->assertOk()->assertDontSee('href="#fee-tuition"', false);
});
