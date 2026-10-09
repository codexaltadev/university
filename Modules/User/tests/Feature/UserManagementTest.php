<?php

/*
| Phase 2.1 — User (C-02) and Role (C-03) core modules.
| Test Plan TC-SEC-04/05, TC-PERM-11/12, TC-DATA-01a; NFR-SEC-02..04; FR-USER-05/07/08, FR-ROLE-04/09.
*/

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Role\Database\Seeders\RoleDatabaseSeeder;
use Modules\User\Auth\Login;
use Modules\User\Filament\Resources\Users\Pages\CreateUser;
use Modules\User\Filament\Resources\Users\Pages\EditUser;
use Modules\User\Filament\Resources\Users\Pages\ListUsers;
use Modules\User\Filament\Resources\Users\UserResource;
use Modules\User\Notifications\UserInvitation;
use Modules\User\Services\UserAccess;
use Platform\Activity\Activity;
use Platform\Modules\ModulePermissions;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('User');
    app(ModulePermissions::class)->sync('Role');
    (new RoleDatabaseSeeder)->run();
});

function attemptLogin(string $email, string $password)
{
    return Livewire::test(Login::class)
        ->fillForm(['email' => $email, 'password' => $password])
        ->call('authenticate');
}

// ─── Sign-in ───────────────────────────────────────────────────────────────

it('signs in, remembers the last login and logs it', function () {
    $user = User::factory()->create(['password' => 'Secret123']);

    attemptLogin($user->email, 'Secret123')->assertHasNoFormErrors();

    expect(auth()->id())->toBe($user->id)
        ->and($user->fresh()->last_login_at)->not->toBeNull()
        ->and(Activity::where('log_name', 'auth')->where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();
});

it('logs a failed login with the email but never the password', function () {
    $user = User::factory()->create(['password' => 'Secret123']);

    attemptLogin($user->email, 'wrong-password')->assertHasFormErrors(['email']);

    $entry = Activity::where('event', 'login_failed')->first();
    expect($entry->properties['email'])->toBe($user->email)
        ->and(json_encode($entry->toArray()))->not->toContain('wrong-password');
});

it('locks an account for 15 minutes after 5 wrong passwords (NFR-SEC-03)', function () {
    $user = User::factory()->create(['password' => 'Secret123']);

    foreach (range(1, 5) as $_) {
        attemptLogin($user->email, 'wrong');
    }

    attemptLogin($user->email, 'Secret123')
        ->assertHasFormErrors(['email' => __('user::user.login.locked', ['minutes' => 15])]);
    expect(auth()->check())->toBeFalse();

    $this->travel(16)->minutes();
    attemptLogin($user->email, 'Secret123')->assertHasNoFormErrors();
    expect(auth()->id())->toBe($user->id);
});

it('tells an inactive account so, and ends its open session (FR-USER-05, AC-USER-01)', function () {
    $user = User::factory()->create(['password' => 'Secret123', 'is_active' => false]);

    attemptLogin($user->email, 'Secret123')
        ->assertHasFormErrors(['email' => __('user::user.login.inactive')]);

    $active = makeUser(['ViewAny:User']);
    $this->actingAs($active)->get(UserResource::getUrl('index'))->assertOk();

    $active->deactivate();
    $this->actingAs($active->fresh())->get(UserResource::getUrl('index'))->assertRedirect(Filament::getLoginUrl());
    expect(auth()->check())->toBeFalse();
});

it('requires 2FA for Super Admins when the setting is on (NFR-SEC-04)', function () {
    $this->actingAs(superAdmin())->get(UserResource::getUrl('index'))->assertOk();

    config(['platform.security.super_admin_requires_2fa' => true]);

    $this->actingAs(superAdmin())->get(UserResource::getUrl('index'))->assertRedirectContains('multi-factor');
    $this->actingAs(makeUser(['ViewAny:User']))->get(UserResource::getUrl('index'))->assertOk();   // others not forced
});

// ─── Users screen ─────────────────────────────────────────────────────────

it('shows the users screen only with permission', function () {
    $this->actingAs(makeUser())->get(UserResource::getUrl('index'))->assertForbidden();

    $this->actingAs($admin = makeUser(['ViewAny:User']));
    $other = User::factory()->create(['name' => 'Rahim']);

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$admin, $other]);
});

it('creates a user with roles and scope, and emails an invitation (FR-USER-07)', function () {
    Notification::fake();
    $engineering = faculty('Engineering');
    $this->actingAs(makeUser(['ViewAny:User', 'Create:User']));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Karim',
            'email' => 'karim@example.edu',
            'locale' => 'bn',
            'roles' => [Role::findByName('entity_editor')->id],
            'scope_keys' => ["test.parent:{$engineering->id}"],
            'send_invitation' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $karim = User::where('email', 'karim@example.edu')->first();

    expect($karim->hasRole('entity_editor'))->toBeTrue()
        ->and(app(UserAccess::class)->scopeKeys($karim))->toBe(["test.parent:{$engineering->id}"])
        ->and($karim->invited_at)->not->toBeNull();

    Notification::assertSentTo($karim, UserInvitation::class, fn (UserInvitation $n) => str_contains($n->url, 'password-reset'));
});

it('enforces the password rules when a password is set directly (NFR-SEC-02)', function () {
    $this->actingAs(makeUser(['ViewAny:User', 'Create:User']));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'A', 'email' => 'a@example.edu', 'locale' => 'en', 'send_invitation' => false, 'password' => 'short'])
        ->call('create')
        ->assertHasFormErrors(['password']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'A', 'email' => 'a@example.edu', 'locale' => 'en', 'send_invitation' => false, 'password' => 'Strong123pass'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('protects Super Admins and the Super Admin role', function () {
    $admin = makeUser(['ViewAny:User', 'Create:User', 'Update:User', 'Delete:User']);
    $boss = superAdmin();
    $this->actingAs($admin);

    expect($admin->can('update', $boss))->toBeFalse()
        ->and($admin->can('delete', $admin))->toBeFalse()                 // never yourself
        ->and($boss->can('update', $admin))->toBeTrue();

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'x@example.edu', 'locale' => 'en', 'roles' => [Role::findByName('super_admin')->id]])
        ->call('create')
        ->assertHasFormErrors(['roles']);

    expect(Role::findByName('super_admin')->is_system)->toBeTruthy()
        ->and($boss->can('delete', Role::findByName('super_admin')))->toBeFalse()
        ->and($boss->can('delete', Role::findByName('viewer')))->toBeTrue();
});

it('keeps the scope in sync when a user is edited', function () {
    $cse = department(faculty(), 'CSE');
    $user = User::factory()->create();
    $this->actingAs(makeUser(['ViewAny:User', 'Update:User']));

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['scope_keys' => ["test.child:{$cse->id}"]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(UserAccess::class)->scopeKeys($user))->toBe(["test.child:{$cse->id}"]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['scope_keys' => []])
        ->call('save');

    expect(app(UserAccess::class)->scopeKeys($user))->toBe([]);
});

it('shows what a user can really do and where (FR-ROLE-09)', function () {
    $engineering = faculty('Engineering');
    $cse = department($engineering, 'CSE');
    $user = makeUser(['ViewAny:User', 'View:User'], [$engineering]);

    $effective = app(UserAccess::class)->effective($user);

    expect($effective['super_admin'])->toBeFalse()
        ->and($effective['permissions']->all())->toBe(['View:User', 'ViewAny:User'])
        ->and($effective['scope'])->toContain("test.parent:{$engineering->id}", "test.child:{$cse->id}");
});
