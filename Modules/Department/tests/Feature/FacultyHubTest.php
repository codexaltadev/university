<?php

/*
| The Faculty editor's "Departments" part (UI/UX Part A §5.2.1): added by the Department
| module through $r->entityFormPart(); the list is this Faculty's and scope-filtered, read
| only; "Add department" presets and fixes the Faculty and returns to the Faculty. The
| Faculty page has no Hub side menu.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Parts\FacultyDepartmentsPart;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Pages\CreateDepartment;
use Modules\Department\Models\Department;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Filament\Resources\Faculties\Pages\EditFaculty;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\Hub\EntityFormPart;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faculty');
    app(ModulePermissions::class)->sync('Department');

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->fbe = Faculty::factory()->create(['short_name' => 'FBE']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Computer Science and Engineering'], 'short_name' => 'CSE']);
    $this->bba = Department::factory()->create(['faculty_id' => $this->fbe->id, 'name' => ['en' => 'Business Administration'], 'short_name' => 'BBA']);
});

it('registers the Departments part for the Faculty editor through the registry', function () {
    expect(EntityFormPart::partsFor(Faculty::class))->toContain(FacultyDepartmentsPart::class)
        ->and(FacultyResource::hasPage('departments'))->toBeFalse();
});

it('lists only the Faculty\'s departments at the end of its editor, without a side menu', function () {
    $this->actingAs(superAdmin());

    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fsit]))->assertOk()
        ->assertDontSee('fi-page-sub-navigation', false);

    // the editor itself (the top bar's "Working in" lists every department)
    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->assertSeeInOrder(['Departments', 'CSE — Computer Science and Engineering'])
        ->assertDontSee('Business Administration')
        ->assertSee(DepartmentResource::getUrl('create', ['faculty' => $this->fsit->id]), false)
        ->assertSee(DepartmentResource::getUrl('edit', ['record' => $this->cse]), false);
});

it('creates a department with the Faculty fixed, and goes back to the Faculty', function () {
    $this->actingAs(superAdmin());

    Livewire::withQueryParams(['faculty' => $this->fsit->id])->test(CreateDepartment::class)
        ->assertSet('hubFaculty', $this->fsit->id)
        ->assertFormSet(['faculty_id' => $this->fsit->id])
        ->assertFormFieldIsDisabled('faculty_id')
        ->fillForm(['faculty_id' => $this->fbe->id, 'name' => ['en' => 'Software Engineering'], 'short_name' => 'SWE', 'web_address' => 'swe'])   // a tampered Faculty is ignored
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(FacultyResource::getUrl('edit', ['record' => $this->fsit]));

    expect(Department::query()->where('short_name', 'SWE')->sole()->faculty_id)->toBe($this->fsit->id);
});

it('follows permission and scope', function () {
    $facultyUser = makeUser(['ViewAny:Faculty', 'View:Faculty', 'Update:Faculty', 'ViewAny:Department', 'View:Department', 'Create:Department'], [$this->fsit]);
    $this->actingAs($facultyUser);

    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->assertSee('Computer Science and Engineering')
        ->assertDontSee(DepartmentResource::getUrl('edit', ['record' => $this->cse]), false);              // no Update:Department
    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fbe]))->assertNotFound();              // another Faculty
    $this->get('/admin/departments/create?faculty='.$this->fbe->id)->assertNotFound();                   // cannot preset another Faculty
});

it('leaves the part out without the department permission', function () {
    $this->actingAs(makeUser(['ViewAny:Faculty', 'View:Faculty', 'Update:Faculty'], [$this->fsit]));

    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fsit]))->assertOk()
        ->assertDontSee('Computer Science and Engineering');
});
