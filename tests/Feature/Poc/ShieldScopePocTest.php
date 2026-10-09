<?php

/*
| Phase 0 — Shield + Scope proof of concept (SAD Part A §7.4, Test Plan §5).
*/

use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Filament\FaqPlugin;
use Modules\Faq\Filament\Resources\Faqs\FaqResource;
use Modules\Faq\Models\Faq;
use Modules\Faq\Policies\FaqPolicy;
use Modules\Office\Filament\OfficePlugin;
use Modules\Office\Models\Office;
use Nwidart\Modules\Facades\Module;
use Platform\Filament\Resources\Roles\Pages\EditRole;
use Platform\Modules\Manifest;
use Platform\Modules\ModulePermissions;
use Platform\Policies\ScopedPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faq');
    app(ModulePermissions::class)->sync('Office');
    Role::findOrCreate(config('filament-shield.super_admin.name'), 'web');

    $this->engineering = faculty('Engineering');
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->bba = department(faculty('Business'), 'BBA');
});

// ─── POC-1 ──────────────────────────────────────────────────────────────────

it('POC-1: syncing one module creates only its permissions and leaves others untouched', function () {
    $modules = app(ModulePermissions::class);
    $modules->removeAll(Manifest::fromDirectory(base_path('Modules/Faq')));

    $othersBefore = Permission::orderBy('id')->get(['id', 'name'])->toArray();

    $keys = $modules->sync('Faq');

    expect($keys)->toHaveCount(12)
        ->each->toEndWith(':Faq');

    expect(Permission::whereIn('name', $keys)->count())->toBe(12);

    expect(Permission::whereNotIn('name', $keys)->orderBy('id')->get(['id', 'name'])->toArray())
        ->toBe($othersBefore);
});

it('POC-1: finds module resources without the panel (works for inactive modules too)', function () {
    Module::find('Faq')->disable();

    expect(app(ModulePermissions::class)->resourcesOf('Faq'))->toBe([FaqResource::class]);
});

// ─── POC-2 ──────────────────────────────────────────────────────────────────

it('POC-2: generated policy extends ScopedPolicy', function () {
    expect(get_parent_class(FaqPolicy::class))
        ->toBe(ScopedPolicy::class);
});

it('POC-2: a CSE user cannot edit an EEE record, but can edit a CSE record', function () {
    $cse = makeUser(['ViewAny:Faq', 'View:Faq', 'Update:Faq'], [$this->cse]);
    $cseFaq = Faq::factory()->shownIn($this->cse)->create();
    $eeeFaq = Faq::factory()->shownIn($this->eee)->create();

    expect($cse->can('update', $cseFaq))->toBeTrue()
        ->and($cse->can('update', $eeeFaq))->toBeFalse();

    // AppliesUserScope (1C) hides it from the panel's queries: not even its existence is revealed
    $this->actingAs($cse)
        ->get(FaqResource::getUrl('edit', ['record' => $eeeFaq]))
        ->assertNotFound();

    $this->actingAs($cse)
        ->get(FaqResource::getUrl('edit', ['record' => $cseFaq]))
        ->assertOk();
});

it('POC-2: permission is still required inside the scope', function () {
    $viewer = makeUser(['ViewAny:Faq', 'View:Faq'], [$this->cse]);
    $faq = Faq::factory()->shownIn($this->cse)->create();

    expect($viewer->can('view', $faq))->toBeTrue()
        ->and($viewer->can('update', $faq))->toBeFalse();
});

it('POC-2: users without scope and super admins are not restricted by scope', function () {
    $admin = makeUser(['Update:Faq']);
    $eeeFaq = Faq::factory()->shownIn($this->eee)->create();

    expect($admin->can('update', $eeeFaq))->toBeTrue()
        ->and(superAdmin()->can('update', $eeeFaq))->toBeTrue();
});

it('POC-2: a faculty scope includes its departments (hierarchy)', function () {
    $dean = makeUser(['Update:Faq'], [$this->engineering]);

    expect($dean->can('update', Faq::factory()->shownIn($this->eee)->create()))->toBeTrue()
        ->and($dean->can('update', Faq::factory()->shownIn($this->bba)->create()))->toBeFalse();
});

// ─── POC-3 ──────────────────────────────────────────────────────────────────

it('POC-3: creating an office creates its own entity permission', function () {
    $office = Office::create(['name' => 'Registrar Office']);

    expect(Permission::where('name', "Manage:Office#{$office->id}")->exists())->toBeTrue();
});

it('POC-3: role editor shows the entity permission and saves it', function () {
    $registrar = Office::create(['name' => 'Registrar Office']);
    Office::create(['name' => 'Proctor Office']);
    $role = Role::create(['name' => 'Office Editor', 'guard_name' => 'web']);

    $this->actingAs(superAdmin());

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertSee('Entity permissions')
        ->assertSee('Manage: Registrar Office')
        ->assertSee('Manage: Proctor Office')
        ->fillForm(['entity_permissions_office' => ["Manage:Office#{$registrar->id}"]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->hasPermissionTo("Manage:Office#{$registrar->id}"))->toBeTrue();
});

it('POC-3: an office editor can manage only the office they were given', function () {
    $registrar = Office::create(['name' => 'Registrar Office']);
    $proctor = Office::create(['name' => 'Proctor Office']);

    $editor = makeUser(['ViewAny:Office', 'View:Office', 'Update:Office', "Manage:Office#{$registrar->id}"]);

    expect($editor->can('update', $registrar))->toBeTrue()
        ->and($editor->can('update', $proctor))->toBeFalse();
});

// ─── POC-4 ──────────────────────────────────────────────────────────────────

it('POC-4: only active modules register their Filament plugin', function () {
    $plugins = fn (): array => array_map('get_class', (fn () => $this->activeModulePlugins())->call(new AdminPanelProvider(app())));

    expect($plugins())->toContain(FaqPlugin::class, OfficePlugin::class);

    Module::find('Faq')->disable();

    expect($plugins())->toContain(OfficePlugin::class)
        ->not->toContain(FaqPlugin::class);
});

it('POC-4 control: for an active module, unticking in the form does remove the permission', function () {
    $role = Role::create(['name' => 'Content Editor', 'guard_name' => 'web']);
    $role->givePermissionTo(['ViewAny:Faq', 'Update:Faq', 'ViewAny:Office']);

    $this->actingAs(superAdmin());

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertDontSee('Inactive modules')
        ->fillForm([FaqResource::class => []])
        ->call('save')
        ->assertHasNoFormErrors();

    $role->refresh();

    expect($role->hasPermissionTo('Update:Faq'))->toBeFalse()
        ->and($role->hasPermissionTo('ViewAny:Office'))->toBeTrue();
});

it('POC-4: inactive module permissions are shown separately and kept when a role is saved', function () {
    $role = Role::create(['name' => 'Content Editor', 'guard_name' => 'web']);
    $role->givePermissionTo(['ViewAny:Faq', 'Update:Faq', 'ViewAny:Office']);

    Module::find('Faq')->disable();

    $this->actingAs(superAdmin());

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertSee('Inactive modules')
        ->assertSee('Update:Faq')
        // Simulate the real situation: the inactive module's resource is not in the form
        ->fillForm([FaqResource::class => []])
        ->call('save')
        ->assertHasNoFormErrors();

    $role->refresh();

    expect($role->hasPermissionTo('Update:Faq'))->toBeTrue()
        ->and($role->hasPermissionTo('ViewAny:Faq'))->toBeTrue()
        ->and($role->hasPermissionTo('ViewAny:Office'))->toBeTrue();
});
