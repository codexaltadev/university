<?php

/*
| SiteUsersSeeder: one login per Faculty, Department and Office, each seeing only its own content.
*/

use App\Models\User;
use Database\Seeders\SiteUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Faq\Models\Faq;
use Modules\Office\Filament\Resources\Offices\OfficeResource;
use Modules\Office\Models\Office;
use Modules\User\Filament\Resources\Users\Pages\EditUser;
use Modules\User\Services\UserAccess;
use Nwidart\Modules\Facades\Module;
use Platform\Modules\Manifest;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Scope\ScopeResolver;
use Platform\Scope\UserScopeFilter;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    foreach (['Faculty', 'Department', 'Office', 'Faq', 'Csr'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(ModulePermissions::class)->syncCustom(Manifest::fromDirectory(Module::getModulePath('ActivityLog')));

    $this->fsit = Faculty::factory()->create(['name' => ['en' => 'Science and IT'], 'short_name' => 'FSIT']);
    $this->fe = Faculty::factory()->create(['name' => ['en' => 'Engineering'], 'short_name' => 'FE']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Computer Science'], 'short_name' => 'CSE']);
    $this->eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'name' => ['en' => 'Electrical'], 'short_name' => 'EEE']);
    $this->registrar = Office::create(['name' => ['en' => 'Registrar Office']]);
    $this->transport = Office::create(['name' => ['en' => 'Transport'], 'has_microsite' => false]);

    $this->faq = fn (string $question, object $place): Faq => tap(
        Faq::create(['question' => ['en' => $question], 'answer' => ['en' => '<p>Answer</p>']]),
        fn (Faq $faq) => app(RelationEngine::class)->attach($faq, [$place->getMorphClass().':'.$place->getKey()]),
    );

    $this->seeded = fn (object $site): User => User::query()
        ->whereHas('scopes', fn ($q) => $q->where('scopeable_type', $site->getMorphClass())->where('scopeable_id', $site->getKey()))
        ->firstOrFail();

    $this->questionsSeenBy = function (User $user): array {
        $this->actingAs($user);
        app(ScopeResolver::class)->forget();
        UserScopeFilter::enforce();

        try {
            return Faq::query()->get()->map(fn (Faq $faq): string => $faq->getTranslation('question', 'en'))->sort()->values()->all();
        } finally {
            UserScopeFilter::enforce(false);
        }
    };
});

it('makes one active login per Faculty, Department and every Office', function () {
    $this->seed(SiteUsersSeeder::class);

    expect(User::query()->count())->toBe(6)
        ->and(($this->seeded)($this->fsit)->hasRole('entity_editor'))->toBeTrue()
        ->and(($this->seeded)($this->cse)->hasRole('entity_editor'))->toBeTrue()
        ->and(($this->seeded)($this->registrar)->hasRole('office_editor'))->toBeTrue()
        ->and(($this->seeded)($this->transport)->hasRole('office_editor'))->toBeTrue()   // a contact card too
        ->and(($this->seeded)($this->cse)->is_active)->toBeTrue()
        ->and(($this->seeded)($this->registrar)->email)->toBe('office.'.$this->registrar->slug('en').'@university.test');
});

it('adds only what is missing when run again and keeps a changed password', function () {
    $this->seed(SiteUsersSeeder::class);
    $user = ($this->seeded)($this->cse);
    $user->update(['password' => 'Changed-pass1', 'email' => 'someone@example.edu']);

    $this->seed(SiteUsersSeeder::class);
    expect(User::query()->count())->toBe(6);

    Office::create(['name' => ['en' => 'New Office']]);
    $this->seed(SiteUsersSeeder::class);

    expect(User::query()->count())->toBe(7)
        ->and(Hash::check('Changed-pass1', $user->fresh()->password))->toBeTrue();
});

it('uses the password from .env, otherwise a random one', function () {
    $this->seed(SiteUsersSeeder::class);
    expect(Hash::check('From-env-pass1', ($this->seeded)($this->cse)->password))->toBeFalse();

    User::query()->forceDelete();
    putenv('SITE_USERS_PASSWORD=From-env-pass1');

    try {
        $this->seed(SiteUsersSeeder::class);
    } finally {
        putenv('SITE_USERS_PASSWORD');
    }

    expect(Hash::check('From-env-pass1', ($this->seeded)($this->cse)->password))->toBeTrue();
});

it('shows each login only its own content, Offices too', function () {
    ($this->faq)('CSE question', $this->cse);
    ($this->faq)('EEE question', $this->eee);
    ($this->faq)('FSIT question', $this->fsit);
    ($this->faq)('Registrar question', $this->registrar);
    ($this->faq)('Transport question', $this->transport);

    $this->seed(SiteUsersSeeder::class);

    expect(($this->questionsSeenBy)(($this->seeded)($this->registrar)))->toBe(['Registrar question'])
        ->and(($this->questionsSeenBy)(($this->seeded)($this->transport)))->toBe(['Transport question'])
        ->and(($this->questionsSeenBy)(($this->seeded)($this->cse)))->toBe(['CSE question'])
        ->and(($this->questionsSeenBy)(($this->seeded)($this->fsit)))->toBe(['CSE question', 'FSIT question']);   // with its departments
});

it('lets a login edit its own site only', function () {
    $this->seed(SiteUsersSeeder::class);
    $office = ($this->seeded)($this->registrar);
    $department = ($this->seeded)($this->cse);

    expect($office->can('update', $this->registrar))->toBeTrue()
        ->and($office->can('update', $this->transport))->toBeFalse()
        ->and($department->can('update', $this->cse))->toBeTrue()
        ->and($department->can('update', $this->eee))->toBeFalse()
        ->and($department->can('create', Department::class))->toBeFalse()
        ->and($department->can('delete', $this->cse))->toBeFalse()
        ->and($office->can('delete', $this->registrar))->toBeFalse();
});

it('lists only the login\'s own office and answers 404 for another', function () {
    $this->seed(SiteUsersSeeder::class);
    $this->actingAs(($this->seeded)($this->registrar));

    $this->get(OfficeResource::getUrl('index'))->assertOk()->assertSee('Registrar Office')->assertDontSee('Transport');
    $this->get(OfficeResource::getUrl('edit', ['record' => $this->registrar]))->assertOk();
    $this->get(OfficeResource::getUrl('edit', ['record' => $this->transport]))->assertNotFound();
});

it('offers sites in "Works on", Offices too, and an Admin can save an office login', function () {
    $this->seed(SiteUsersSeeder::class);
    $user = ($this->seeded)($this->registrar);
    $user->update(['name' => 'Before']);

    $options = collect(app(UserAccess::class)->scopeOptions());
    expect($options->flatMap(fn (array $group): array => array_keys($group))->all())
        ->toContain('office.office:'.$this->registrar->id, 'department.department:'.$this->cse->id, 'faculty.faculty:'.$this->fsit->id)
        ->and($options->keys()->all())->not->toContain('Tuitions', 'Members', 'Alumni', 'Faculty Members');

    $this->actingAs(superAdmin());
    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['name' => 'After'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->name)->toBe('After')
        ->and($user->fresh()->scopes()->get()->map->key()->all())->toBe(['office.office:'.$this->registrar->id]);
});

it('gives the roles no System, main site or Activity log permissions', function () {
    $this->seed(SiteUsersSeeder::class);

    $names = Role::findByName('entity_editor')->permissions->pluck('name');

    expect($names)->toContain('Update:Faq', 'Update:Department', 'Update:Activity')
        ->not->toContain('PublishMain:Faq', 'HighlightMain:Faq', 'ManageMain:Menu', 'ForceDelete:Faq', 'ViewAny:ActivityLog', 'ViewAny:User', 'Create:Department', 'ManageAll:MediaItem');
});

it('leaves a role alone once an admin has ticked it', function () {
    Role::findOrCreate('entity_editor', 'web')->givePermissionTo('View:Faq');

    $this->seed(SiteUsersSeeder::class);

    expect(Role::findByName('entity_editor')->permissions->pluck('name')->all())->toBe(['View:Faq']);
});
