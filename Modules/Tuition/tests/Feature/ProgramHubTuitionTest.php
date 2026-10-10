<?php

/*
| Program Hub › Fees (FR-GEN-86, UI/UX Part A §4.3, SRS M-10 v1.1): Tuition Fees — edited
| by who has the tuition permissions (Admission Office), read by entity users in their
| scope.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Hub\ProgramTuitions;
use Modules\Tuition\Filament\Resources\Tuitions\Pages\CreateTuition;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Program', 'Tuition'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->local = FeeCategory::query()->create(['name' => ['en' => 'Local Tuition Fee'], 'code' => 'local', 'currency' => 'BDT']);
    $dept = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id]);
    $this->bsc = Program::factory()->create(['department_id' => $dept->id, 'name' => ['en' => 'BSc in CSE']]);
    $this->other = Program::factory()->create(['department_id' => $dept->id, 'name' => ['en' => 'BSc in EEE']]);
    $this->tuition = Tuition::query()->create(['program_id' => $this->bsc->id, 'fee_category_id' => $this->local->id, 'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500]);
    Tuition::query()->create(['program_id' => $this->other->id, 'fee_category_id' => $this->local->id, 'totals_overridden' => true, 'tuition_min' => 500000, 'total_min' => 700000]);
});

it('lets the Admission Office manage the program\'s tuition from the Hub', function () {
    $office = makeUser(['ViewAny:Program', 'View:Program', 'ViewAny:Tuition', 'View:Tuition', 'Create:Tuition', 'Update:Tuition']);   // no scope: every program
    $this->actingAs($office);

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertForbidden();             // the editor stays Update:Program
    $this->get(ProgramResource::getUrl('view', ['record' => $this->bsc]))->assertOk()                     // read only, with the Hub
        ->assertSee(ProgramTuitions::getUrl(['record' => $this->bsc]), false);

    $this->get(ProgramTuitions::getUrl(['record' => $this->bsc]))->assertOk()
        ->assertSeeInOrder(['Fees', 'Tuition Fees'])
        ->assertSee('৳ 952,500')->assertDontSee('৳ 700,000')
        ->assertSee('/admin/tuition/tuitions/create?program='.$this->bsc->id, false);

    Livewire::withQueryParams(['program' => $this->bsc->id])->test(CreateTuition::class)
        ->assertFormFieldIsDisabled('program_id')
        ->fillForm(['program_id' => $this->other->id, 'fee_category_id' => $this->local->id, 'credit_min' => 40])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ProgramTuitions::getUrl(['record' => $this->bsc]));
    expect(Tuition::query()->where('program_id', $this->bsc->id)->count())->toBe(2);
});

it('shows a program user their tuition read only', function () {
    $programUser = makeUser(['ViewAny:Program', 'View:Program', 'Update:Program', 'ViewAny:Tuition', 'View:Tuition'], [$this->bsc]);
    $this->actingAs($programUser);

    $this->get(ProgramTuitions::getUrl(['record' => $this->bsc]))->assertOk()
        ->assertSee('৳ 952,500')
        ->assertDontSee('/admin/tuition/tuitions/create', false);

    Livewire::test(ProgramTuitions::class, ['record' => $this->bsc->getRouteKey()])
        ->assertTableActionHidden('edit', $this->tuition)
        ->call('updateTableColumnState', 'is_active', (string) $this->tuition->getKey(), false);
    expect($this->tuition->fresh()->is_active)->toBeTrue();

    $this->get(ProgramTuitions::getUrl(['record' => $this->other]))->assertNotFound();                   // another program
});
