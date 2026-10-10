<?php

/*
| Website addresses (SRS FR-GEN-34 to 36, UI/UX Part B §3 v0.2): /faculties/fsit, /fsit/cse,
| /fsit/cse/bsc-in-cse with the trail Home › FSIT › CSE › program; "/fsit" and DIU's old
| /department/… forms redirect; reserved words and taken addresses are refused on save.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Resources\Departments\Pages\CreateDepartment;
use Modules\Department\Models\Department;
use Modules\Faculty\Filament\Resources\Faculties\Pages\CreateFaculty;
use Modules\Faculty\Models\Faculty;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Filament\Resources\Programs\Pages\CreateProgram;
use Modules\Program\Models\Program;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program', 'Page'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT', 'name' => ['en' => 'Faculty of Science and IT']]);
    $this->fsit->changeSlug('en', 'fsit');
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE', 'name' => ['en' => 'Computer Science and Engineering']]);
    $this->cse->changeSlug('en', 'cse');
    $this->bsc = Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'BSc in CSE']]);
    $this->bsc->changeSlug('en', 'bsc-in-cse');
});

it('puts every microsite under its parent, with the trail', function () {
    expect($this->fsit->url())->toBe('/faculties/fsit')
        ->and($this->cse->url())->toBe('/fsit/cse')
        ->and($this->bsc->url())->toBe('/fsit/cse/bsc-in-cse');

    $this->get('/faculties/fsit')->assertOk();
    $this->get('/fsit/cse')->assertOk();
    $this->get('/fsit/cse/bsc-in-cse')->assertOk()
        ->assertSeeInOrder(['Home', 'FSIT', 'CSE', 'BSc in CSE'])
        ->assertSee('"@type":"BreadcrumbList"', false);

    $this->get('/fsit')->assertRedirect('/faculties/fsit');                                        // the Faculty alone
    $this->get('/department/cse')->assertRedirect('/fsit/cse');                                    // DIU's old forms
    $this->get('/department/cse/program/bsc-in-cse')->assertRedirect('/fsit/cse/bsc-in-cse');
    $this->get('/program/bsc-in-cse')->assertRedirect('/fsit/cse/bsc-in-cse');
});

it('follows a move and a new address with a 301', function () {
    $fe = Faculty::factory()->create(['short_name' => 'FE']);
    $fe->changeSlug('en', 'fe');
    $this->cse->update(['faculty_id' => $fe->id]);

    $this->get('/fsit/cse/bsc-in-cse')->assertRedirect('/fe/cse/bsc-in-cse');
    $this->cse->changeSlug('en', 'computer-science');
    $this->get('/fe/cse')->assertRedirect('/fe/computer-science');
});

it('refuses addresses the website uses itself or another record has (FR-GEN-35)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateFaculty::class)
        ->fillForm(['name' => ['en' => 'News Faculty'], 'short_name' => 'NF', 'web_address' => 'news'])
        ->call('create')
        ->assertHasFormErrors(['web_address']);

    Livewire::test(CreatePage::class)                                                    // a main site page "about" …
        ->fillForm(['title' => ['en' => 'About'], 'owner_key' => 'site:0'])
        ->call('create')->assertHasNoFormErrors();
    Livewire::test(CreateFaculty::class)                                                 // … so no Faculty "about"
        ->fillForm(['name' => ['en' => 'About Faculty'], 'short_name' => 'AF', 'web_address' => 'about'])
        ->call('create')
        ->assertHasFormErrors(['web_address']);
    Livewire::test(CreatePage::class)                                                    // and no main site page "fsit"
        ->fillForm(['title' => ['en' => 'FSIT'], 'owner_key' => 'site:0', 'web_address' => 'fsit'])
        ->call('create')
        ->assertHasFormErrors(['web_address']);

    Livewire::test(CreatePage::class)                                                    // a CSE page "bsc-in-cse" would hide the program
        ->fillForm(['title' => ['en' => 'BSc'], 'owner_key' => $this->cse->getMorphClass().':'.$this->cse->id, 'web_address' => 'bsc-in-cse'])
        ->call('create')
        ->assertHasFormErrors(['web_address']);

    Livewire::withQueryParams(['department' => $this->cse->id])->test(CreateProgram::class)
        ->assertFormFieldExists('web_address', fn ($field): bool => $field->getPrefixLabel() === '/fsit/cse/')
        ->fillForm(['level_term_id' => ProgramFactory::level('Graduate'), 'name' => ['en' => 'News'], 'short_name' => 'NW', 'web_address' => 'news'])
        ->call('create')
        ->assertHasFormErrors(['web_address']);

    Livewire::withQueryParams(['faculty' => $this->fsit->id])->test(CreateDepartment::class)
        ->assertFormFieldExists('web_address', fn ($field): bool => $field->getPrefixLabel() === '/fsit/')
        ->fillForm(['name' => ['en' => 'Software Engineering'], 'short_name' => 'SWE', 'web_address' => 'swe'])
        ->call('create')
        ->assertHasNoFormErrors();
});
