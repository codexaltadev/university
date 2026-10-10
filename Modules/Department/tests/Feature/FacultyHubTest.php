<?php

/*
| Entity Hub POC (FR-GEN-86, UI/UX Part A §4.3): the Faculty Hub gets a "Departments"
| part from the Department module through $r->entityPage(); the list is this Faculty's
| and scope-filtered; "New department" presets and fixes the Faculty and returns to the Hub.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Hub\FacultyDepartments;
use Modules\Department\Filament\Resources\Departments\Pages\CreateDepartment;
use Modules\Department\Models\Department;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Models\Faculty;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faculty');
    app(ModulePermissions::class)->sync('Department');

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->fbe = Faculty::factory()->create(['short_name' => 'FBE']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Computer Science and Engineering'], 'short_name' => 'CSE']);
    $this->bba = Department::factory()->create(['faculty_id' => $this->fbe->id, 'name' => ['en' => 'Business Administration'], 'short_name' => 'BBA']);
});

it('registers the Departments part in the Faculty Hub through the registry', function () {
    expect(collect(app(Registry::class)->all('entityPage'))->pluck('class'))->toContain(FacultyDepartments::class)
        ->and(FacultyResource::hubPageClasses())->toContain(FacultyDepartments::class)
        ->and(FacultyResource::hasPage('departments'))->toBeTrue();
});

it('shows the Hub sub-menu on the Faculty and lists only its departments', function () {
    $this->actingAs(superAdmin());

    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fsit]))->assertOk()
        ->assertSee(FacultyDepartments::getUrl(['record' => $this->fsit]), false)
        ->assertSee('Profile');

    $this->get(FacultyDepartments::getUrl(['record' => $this->fsit]))->assertOk()
        ->assertSee('Computer Science and Engineering')
        ->assertDontSee('Business Administration')
        ->assertSee('/admin/departments/create?faculty='.$this->fsit->id, false);
});

it('creates a department from the Hub with the Faculty fixed, and goes back to the Hub', function () {
    $this->actingAs(superAdmin());

    Livewire::withQueryParams(['faculty' => $this->fsit->id])->test(CreateDepartment::class)
        ->assertSet('hubFaculty', $this->fsit->id)
        ->assertFormSet(['faculty_id' => $this->fsit->id])
        ->assertFormFieldIsDisabled('faculty_id')
        ->fillForm(['faculty_id' => $this->fbe->id, 'name' => ['en' => 'Software Engineering'], 'short_name' => 'SWE', 'web_address' => 'swe'])   // a tampered Faculty is ignored
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(FacultyDepartments::getUrl(['record' => $this->fsit]));

    expect(Department::query()->where('short_name', 'SWE')->sole()->faculty_id)->toBe($this->fsit->id);
});

it('follows permission and scope', function () {
    $facultyUser = makeUser(['ViewAny:Faculty', 'View:Faculty', 'Update:Faculty', 'ViewAny:Department', 'View:Department', 'Create:Department'], [$this->fsit]);
    $this->actingAs($facultyUser);

    $this->get(FacultyDepartments::getUrl(['record' => $this->fsit]))->assertOk()->assertSee('Computer Science and Engineering');
    $this->get(FacultyDepartments::getUrl(['record' => $this->fbe]))->assertNotFound();                  // another Faculty
    $this->get('/admin/departments/create?faculty='.$this->fbe->id)->assertNotFound();                   // cannot preset another Faculty

    // Without the department permission the part is not offered
    $this->actingAs(makeUser(['ViewAny:Faculty', 'View:Faculty', 'Update:Faculty'], [$this->fsit]));
    $this->get(FacultyResource::getUrl('edit', ['record' => $this->fsit]))->assertOk()
        ->assertDontSee(FacultyDepartments::getUrl(['record' => $this->fsit]), false);
    $this->get(FacultyDepartments::getUrl(['record' => $this->fsit]))->assertForbidden();
});
