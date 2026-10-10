<?php

/*
| Editor parts (UI/UX Part A §5.2.1): the Department editor's Programs (new program with
| the Department preset and fixed), the Faculty editor's Programs (all of its departments'),
| and the Program editor: a new program step by step, an existing one part by part with
| what is filled in (UI/UX Part A §5.2 v1.2).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Pages\EditDepartment;
use Modules\Department\Models\Department;
use Modules\Faculty\Filament\Resources\Faculties\Pages\EditFaculty;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Filament\Resources\Programs\Pages\CreateProgram;
use Modules\Program\Filament\Resources\Programs\Pages\EditProgram;
use Modules\Program\Filament\Resources\Programs\Pages\ListPrograms;
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

it('lists the Department\'s programs at the end of its editor, and adds one with the Department fixed', function () {
    $this->actingAs(superAdmin());

    Livewire::test(EditDepartment::class, ['record' => $this->cse->getRouteKey()])   // the editor (the top bar lists every program)
        ->assertSee('BSc in Computer Science')->assertDontSee('BSc in Software Engineering')
        ->assertSee(ProgramResource::getUrl('create', ['department' => $this->cse->id]), false)
        ->assertSee(ProgramResource::getUrl('edit', ['record' => $this->bscCse]), false);

    Livewire::withQueryParams(['department' => $this->cse->id])->test(CreateProgram::class)
        ->assertFormFieldIsDisabled('department_id')
        ->fillForm(['department_id' => $this->bba->id, 'level_term_id' => ProgramFactory::level('Graduate'), 'name' => ['en' => 'MSc in CSE'], 'short_name' => 'MSc', 'web_address' => 'msc-cse'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(DepartmentResource::getUrl('edit', ['record' => $this->cse]));

    expect(Program::query()->where('short_name', 'MSc')->sole()->department_id)->toBe($this->cse->id);
});

it('lists every program of the Faculty\'s departments in its editor; new ones are added from a Department', function () {
    $this->actingAs(superAdmin());

    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->assertSee('BSc in Computer Science')->assertSee('BSc in Software Engineering')
        ->assertDontSee('Master of Business Administration')
        ->assertDontSee('/admin/programs/create', false);
});

it('enters a new program step by step, its lists included, in one go', function () {
    $this->actingAs(superAdmin());

    $this->get(ProgramResource::getUrl('create'))->assertOk()
        ->assertSeeInOrder(['Basic info', 'Program details', 'Eligibility', 'Majors', 'Program options', 'Career &amp; industry', 'Facilitators &amp; people', 'Materials &amp; memberships', 'Summary &amp; SEO'], false);

    Livewire::test(CreateProgram::class)
        ->fillForm([
            'department_id' => $this->cse->id, 'level_term_id' => ProgramFactory::level('Undergraduate'),
            'name' => ['en' => 'BSc in Data Science'], 'short_name' => 'BSc DS', 'web_address' => 'bsc-ds',
            'summary' => ['en' => '<p>Data all the way.</p>'], 'total_credits' => 144,
            'eligibility_general' => ['en' => '<p>HSC GPA 2.5</p>'],
            'majors' => [['title' => ['en' => 'Machine Learning'], 'is_active' => true]],
            'career_paths' => [['title' => ['en' => 'Data Analyst'], 'is_active' => true]],
            'facilitators' => [['name' => 'Ayesha Rahman', 'is_active' => true]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $program = Program::query()->where('short_name', 'BSc DS')->sole();
    expect($program->majors()->count())->toBe(1)
        ->and($program->listItems()->sole()->list_type)->toBe('career_path')
        ->and($program->facilitators()->sole()->name)->toBe('Ayesha Rahman');

    // The editor shows each part with what is filled in: no picture yet, no materials
    $this->get(ProgramResource::getUrl('edit', ['record' => $program]))->assertOk()
        ->assertSee('5 of 7 parts filled in')
        ->assertSeeInOrder(['Basic info', 'Program details', 'Eligibility', 'Majors'], false);
    $editor = Livewire::test(EditProgram::class, ['record' => $program->getRouteKey()]);
    expect(collect($editor->get('data.majors'))->pluck('title.en')->all())->toBe(['Machine Learning']);
});

it('follows scope and permission; a viewer cannot switch programs off', function () {
    $viewer = makeUser(['ViewAny:Department', 'View:Department', 'ViewAny:Program', 'View:Program'], [$this->cse]);
    $this->actingAs($viewer);

    $this->get('/admin/programs/create?department='.$this->bba->id)->assertNotFound();    // outside the scope
    $this->get('/admin/programs/create?department='.$this->cse->id)->assertForbidden();   // no Create:Program at all

    Livewire::test(ListPrograms::class)
        ->assertCanSeeTableRecords([$this->bscCse])->assertCanNotSeeTableRecords([$this->mba])
        ->call('updateTableColumnState', 'is_active', (string) $this->bscCse->getKey(), false);
    expect($this->bscCse->fresh()->is_active)->toBeTrue();                                    // the toggle is disabled for a viewer
});
