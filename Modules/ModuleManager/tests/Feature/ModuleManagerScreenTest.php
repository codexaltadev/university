<?php

/*
| Phase 2.2 — Module Manager screen (C-01, UI/UX Part A §7.9).
| Real installs from ZIP are in tests/Lifecycle/ModuleManagerScreenTest.php.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleResource;
use Modules\ModuleManager\Filament\Resources\Modules\Pages\ListModules;
use Modules\ModuleManager\Filament\Resources\Modules\Pages\ViewModule;
use Platform\Models\CoreModule;
use Platform\Modules\ModulePermissions;
use Platform\Modules\ModuleRegistry;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('User');
});

function moduleRow(string $slug): CoreModule
{
    return CoreModule::where('slug', $slug)->firstOrFail();
}

it('is for Super Admins only', function () {
    $this->actingAs(makeUser(['ViewAny:User']))->get(ModuleResource::getUrl('index'))->assertForbidden();
    $this->actingAs(superAdmin())->get(ModuleResource::getUrl('index'))->assertOk();
});

it('filters by tab', function () {
    $this->actingAs(superAdmin());
    $page = Livewire::test(ListModules::class);
    CoreModule::where('slug', 'faq')->update(['status' => CoreModule::STATUS_INSTALLED]);

    $page->set('activeTab', 'core')
        ->assertCanSeeTableRecords([moduleRow('user')])
        ->assertCanNotSeeTableRecords([moduleRow('faq')]);

    $page->set('activeTab', 'inactive')
        ->assertCanSeeTableRecords([moduleRow('faq')])
        ->assertCanNotSeeTableRecords([moduleRow('user')]);

    $page->set('activeTab', 'active')
        ->assertCanSeeTableRecords([moduleRow('user')])
        ->assertCanNotSeeTableRecords([moduleRow('faq')]);
});

it('lists every module, recording bundled ones on first visit', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ListModules::class)
        ->assertCanSeeTableRecords(CoreModule::whereIn('slug', ['faq', 'office', 'role', 'user', 'module-manager'])->get())
        ->assertTableColumnStateSet('status', 'active', moduleRow('faq'));

    expect(CoreModule::where('slug', 'module-manager')->value('type'))->toBe('core');
});

it('deactivates and activates an installable module', function () {
    $this->actingAs(superAdmin());
    $page = Livewire::test(ListModules::class);

    $page->callTableAction('deactivate', moduleRow('faq'))->assertNotified();
    expect(app(ModuleRegistry::class)->isActive('faq'))->toBeFalse();

    Livewire::test(ListModules::class)->callTableAction('activate', moduleRow('faq'))->assertNotified();
    expect(app(ModuleRegistry::class)->isActive('faq'))->toBeTrue();
});

it('offers no deactivate or uninstall for core modules', function (string $slug) {
    $this->actingAs(superAdmin());

    Livewire::test(ListModules::class)
        ->assertTableActionHidden('deactivate', moduleRow($slug))
        ->assertTableActionHidden('uninstall', moduleRow($slug));
})->with(['role', 'user', 'module-manager']);

it('offers uninstall only for an inactive module and asks for the slug before deleting data', function () {
    $this->actingAs(superAdmin());
    Livewire::test(ListModules::class)->assertTableActionHidden('uninstall', moduleRow('faq'));

    Livewire::test(ListModules::class)->callTableAction('deactivate', moduleRow('faq'));

    Livewire::test(ListModules::class)
        ->assertTableActionVisible('uninstall', moduleRow('faq'))
        ->callTableAction('uninstall', moduleRow('faq'), data: ['data' => 'delete', 'confirm' => 'wrong'])
        ->assertHasTableActionErrors(['confirm']);

    expect(CoreModule::where('slug', 'faq')->exists())->toBeTrue();   // nothing removed
});

it('shows module details: README, permissions and the operation log', function () {
    $this->actingAs(superAdmin());
    Livewire::test(ListModules::class)->callTableAction('deactivate', moduleRow('office'));

    Livewire::test(ViewModule::class, ['record' => moduleRow('office')->getRouteKey()])
        ->assertSee('Update:Office')
        ->assertSee('deactivate');
});

it('stays out of the role editor and the permission sync', function () {
    app(ModulePermissions::class)->sync('ModuleManager');

    expect(Permission::where('name', 'like', '%:CoreModule')->exists())->toBeFalse();
});
