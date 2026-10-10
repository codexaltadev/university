<?php

/*
| Microsite screens from an entity (UI/UX Part A §4.1): "Microsite ▾" on the editors opens
| Menus, Header & Footer, Homepage, Statistics and Pages on that entity; "Working in" keeps
| the current microsite between those screens; "My Microsite" takes a scoped user to theirs.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Homepage\Filament\Pages\ManageHomepage;
use Modules\Menu\Filament\Pages\ManageMenus;
use Modules\Page\Filament\Resources\Pages\PageResource;
use Modules\Statistics\Filament\Pages\ManageStatistics;
use Platform\Context\WorkingIn;
use Platform\Filament\Livewire\WorkingInSwitcher;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Menu', 'Homepage', 'Statistics', 'HeaderFooter', 'Page'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    foreach (['Manage:Menu', 'Manage:Homepage'] as $permission) {
        Permission::findOrCreate($permission, 'web');   // module custom permissions (the role editor's catalog)
    }

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE']);
    $this->swe = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'SWE']);
    $this->key = WorkingIn::keyOf($this->cse);
});

it('offers the microsite screens of the entity on its editor', function () {
    $this->actingAs(superAdmin());

    $this->get(DepartmentResource::getUrl('edit', ['record' => $this->cse]))->assertOk()
        ->assertSee('Microsite')
        ->assertSee(e(ManageMenus::getUrl(['owner' => $this->key])), false)
        ->assertSee(e(ManageHomepage::getUrl(['owner' => $this->key])), false)
        ->assertSee(e(ManageStatistics::getUrl(['owner' => $this->key])), false)
        ->assertSee(e(PageResource::urlFor($this->key)), false);
});

it('keeps the microsite worked on between the screens', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ManageMenus::class, ['owner' => $this->key])->assertSet('owner', $this->key);   // opened on CSE
    expect(WorkingIn::current())->toBe($this->key);

    Livewire::test(ManageHomepage::class)->assertSet('owner', $this->key);   // no ?owner: the one worked on

    Livewire::test(WorkingInSwitcher::class)
        ->set('owner', RelationEngine::SITE_KEY)
        ->assertRedirect();
    expect(WorkingIn::current())->toBe(RelationEngine::SITE_KEY);
    Livewire::test(ManageStatistics::class)->assertSet('owner', RelationEngine::SITE_KEY);
});

it('limits "Working in" to the user\'s scope and leads them to "My Microsite"', function () {
    $user = makeUser(['ViewAny:Department', 'View:Department', 'Update:Department', 'Manage:Menu', 'Manage:Homepage'], [$this->cse]);
    $this->actingAs($user);

    expect(WorkingIn::keys())->toBe([$this->key])                                  // no main site, no other department
        ->and(WorkingIn::myMicrositeUrl())->toBe(DepartmentResource::getUrl('edit', ['record' => $this->cse]));

    Livewire::test(WorkingInSwitcher::class)->set('owner', WorkingIn::keyOf($this->swe));   // not theirs: ignored
    expect(WorkingIn::current())->not->toBe(WorkingIn::keyOf($this->swe));

    $this->get('/admin')->assertOk()
        ->assertSee('My Microsite')
        ->assertSee(DepartmentResource::getUrl('edit', ['record' => $this->cse]), false);
});

it('shows nothing of the kind to a user without microsite screens', function () {
    $this->actingAs(makeUser(['ViewAny:Department', 'View:Department', 'Update:Department'], [$this->cse]));

    expect(WorkingInSwitcher::shouldShow())->toBeFalse();
    $this->get(DepartmentResource::getUrl('edit', ['record' => $this->cse]))->assertOk()
        ->assertDontSee(e(ManageMenus::getUrl(['owner' => $this->key])), false);
});
