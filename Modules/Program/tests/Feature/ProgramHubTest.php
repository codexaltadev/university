<?php

/*
| Entity Hubs (FR-GEN-86, UI/UX Part A §4.3): Department Hub › Programs (new program with
| the Department preset and fixed), Faculty Hub › Programs (all of its departments'),
| and the Program Hub with its grouped parts (Program, Program page).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Models\Department;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Filament\Hub\DepartmentPrograms;
use Modules\Program\Filament\Hub\FacultyPrograms;
use Modules\Program\Filament\Resources\Programs\Hub\ProgramMajors;
use Modules\Program\Filament\Resources\Programs\Pages\CreateProgram;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->fbe = Faculty::factory()->create(['short_name' => 'FBE']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE']);
    $this->swe = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'SWE']);
    $this->bba = Department::factory()->create(['faculty_id' => $this->fbe->id, 'short_name' => 'BBA']);
    $this->bscCse = Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'BSc in Computer Science']]);
    $this->bscSwe = Program::factory()->create(['department_id' => $this->swe->id, 'name' => ['en' => 'BSc in Software Engineering']]);
    $this->mba = Program::factory()->create(['department_id' => $this->bba->id, 'name' => ['en' => 'Master of Business Administration']]);
});

it('gives the Department Hub its programs, and creates one with the Department fixed', function () {
    $this->actingAs(superAdmin());

    $this->get(DepartmentResource::getUrl('edit', ['record' => $this->cse]))->assertOk()
        ->assertSee(DepartmentPrograms::getUrl(['record' => $this->cse]), false);

    $this->get(DepartmentPrograms::getUrl(['record' => $this->cse]))->assertOk()
        ->assertSee('BSc in Computer Science')->assertDontSee('BSc in Software Engineering')
        ->assertSee('/admin/programs/create?department='.$this->cse->id, false);

    Livewire::withQueryParams(['department' => $this->cse->id])->test(CreateProgram::class)
        ->assertFormFieldIsDisabled('department_id')
        ->fillForm(['department_id' => $this->bba->id, 'level_term_id' => ProgramFactory::level('Graduate'), 'name' => ['en' => 'MSc in CSE'], 'short_name' => 'MSc', 'web_address' => 'msc-cse'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(DepartmentPrograms::getUrl(['record' => $this->cse]));

    expect(Program::query()->where('short_name', 'MSc')->sole()->department_id)->toBe($this->cse->id);
});

it('gives the Faculty Hub every program of its departments, read here, made in a Department Hub', function () {
    $this->actingAs(superAdmin());

    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fsit]))->assertOk()
        ->assertSee(FacultyPrograms::getUrl(['record' => $this->fsit]), false);

    $this->get(FacultyPrograms::getUrl(['record' => $this->fsit]))->assertOk()
        ->assertSee('BSc in Computer Science')->assertSee('BSc in Software Engineering')
        ->assertDontSee('Master of Business Administration')
        ->assertDontSee('/admin/programs/create', false);
});

it('groups the Program Hub: Program, Program page', function () {
    $this->actingAs(superAdmin());

    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bscCse]))->assertOk()
        ->assertSeeInOrder(['Profile', 'Program page', 'Majors &amp; Specializations', 'Career Paths', 'Industries', 'Facilitators', 'Learning Materials', 'Professional Memberships', 'Program Options'], false)
        ->assertSee(ProgramMajors::getUrl(['record' => $this->bscCse]), false);

    $this->get(ProgramMajors::getUrl(['record' => $this->bscCse]))->assertOk()->assertSee('Majors &amp; Specializations', false);
});

it('follows scope and permission in the Hubs; a viewer cannot switch records off', function () {
    $viewer = makeUser(['ViewAny:Department', 'View:Department', 'ViewAny:Program', 'View:Program'], [$this->cse]);
    $this->actingAs($viewer);

    $this->get(DepartmentPrograms::getUrl(['record' => $this->cse]))->assertOk()->assertSee('BSc in Computer Science');
    $this->get(DepartmentPrograms::getUrl(['record' => $this->bba]))->assertNotFound();
    $this->get('/admin/programs/create?department='.$this->bba->id)->assertNotFound();    // outside the scope
    $this->get('/admin/programs/create?department='.$this->cse->id)->assertForbidden();   // no Create:Program at all

    Livewire::test(DepartmentPrograms::class, ['record' => $this->cse->getRouteKey()])
        ->assertTableColumnStateSet('is_active', true, $this->bscCse)
        ->call('updateTableColumnState', 'is_active', (string) $this->bscCse->getKey(), false);
    expect($this->bscCse->fresh()->is_active)->toBeTrue();                                    // the toggle is disabled for a viewer
});
