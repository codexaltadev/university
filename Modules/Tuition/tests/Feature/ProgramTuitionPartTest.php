<?php

/*
| "Tuition fees" at the end of the Program editor (UI/UX Part A §5.2.1, entityFormPart):
| the program's tuitions read only; the tuition team (Admission Office) adds and edits them
| in Academic › Tuition Fees. The Program page has no Hub side menu any more.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Resources\Tuitions\Pages\CreateTuition;
use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Program', 'Tuition'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->local = FeeCategory::query()->create(['name' => ['en' => 'Local Tuition Fee'], 'code' => 'local', 'currency' => 'BDT']);
    $this->intl = FeeCategory::query()->create(['name' => ['en' => 'International Tuition Fee'], 'code' => 'international', 'currency' => 'USD']);
    $dept = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id]);
    $this->bsc = Program::factory()->create(['department_id' => $dept->id, 'name' => ['en' => 'BSc in CSE']]);
    $this->other = Program::factory()->create(['department_id' => $dept->id, 'name' => ['en' => 'BSc in EEE']]);
    $this->tuition = Tuition::query()->create(['program_id' => $this->bsc->id, 'fee_category_id' => $this->local->id, 'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500]);
    Tuition::query()->create(['program_id' => $this->bsc->id, 'fee_category_id' => $this->intl->id, 'totals_overridden' => true, 'tuition_min' => 9000, 'total_min' => 11000]);   // several per program
    Tuition::query()->create(['program_id' => $this->other->id, 'fee_category_id' => $this->local->id, 'totals_overridden' => true, 'tuition_min' => 500000, 'total_min' => 700000]);
});

it('shows the program\'s tuition fees as the last part of the editor, without a side menu', function () {
    $this->actingAs(superAdmin());

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertOk()
        ->assertSeeInOrder(['Basic info', 'Summary &amp; SEO', 'Tuition Fees'], false)
        ->assertSee('৳ 952,500')->assertSee('International Tuition Fee')
        ->assertDontSee('৳ 700,000')
        ->assertDontSee('/admin/programs/'.$this->bsc->id.'/tuition-fees', false)        // no Hub page
        ->assertSee(TuitionResource::getUrl('create', ['program' => $this->bsc->id]), false)
        ->assertSee(TuitionResource::getUrl('edit', ['record' => $this->tuition]), false);

    $this->get('/admin/programs/'.$this->bsc->id.'/tuition-fees')->assertNotFound();
});

it('lets the Admission Office add a tuition from the program, then go back to it', function () {
    $office = makeUser(['ViewAny:Program', 'View:Program', 'ViewAny:Tuition', 'View:Tuition', 'Create:Tuition', 'Update:Tuition']);   // no scope: every program
    $this->actingAs($office);

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertForbidden();             // the editor stays Update:Program
    $this->get(ProgramResource::getUrl('view', ['record' => $this->bsc]))->assertOk()                     // read only, tuitions included
        ->assertSee('৳ 952,500')
        ->assertSee(TuitionResource::getUrl('create', ['program' => $this->bsc->id]), false);

    Livewire::withQueryParams(['program' => $this->bsc->id])->test(CreateTuition::class)
        ->assertFormFieldIsDisabled('program_id')
        ->fillForm(['program_id' => $this->other->id, 'fee_category_id' => $this->local->id, 'label' => ['en' => 'Evening'], 'credit_min' => 40])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ProgramResource::getUrl('view', ['record' => $this->bsc]));
    expect(Tuition::query()->where('program_id', $this->bsc->id)->count())->toBe(3);
});

it('shows a program user their tuition read only', function () {
    $this->actingAs(makeUser(['ViewAny:Program', 'View:Program', 'Update:Program', 'ViewAny:Tuition', 'View:Tuition'], [$this->bsc]));

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertOk()
        ->assertSee('৳ 952,500')
        ->assertDontSee(TuitionResource::getUrl('create', ['program' => $this->bsc->id]), false)
        ->assertDontSee(TuitionResource::getUrl('edit', ['record' => $this->tuition]), false);
});

it('leaves the part out for who may not see tuitions', function () {
    $this->actingAs(makeUser(['ViewAny:Program', 'View:Program', 'Update:Program'], [$this->bsc]));

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertOk()
        ->assertDontSee('৳ 952,500')->assertDontSee('Tuition Fees');
});
