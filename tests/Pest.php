<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Models\UserScope;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Models\FixtureChild;
use Tests\Fixtures\Models\FixtureParent;
use Tests\TestCase;

/*
| Module status lives in core_modules (ADR-02). With no rows, bundled modules
| follow uwms.default_active, so every test starts with Faq and Office active;
| enabling / disabling inside a test is rolled back with the transaction.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../Modules/*/tests');

/*
| Module install / uninstall runs real migrations (DDL commits in MySQL),
| so these tests rebuild the database themselves instead of using a transaction.
*/
pest()->extend(TestCase::class)->in('Lifecycle');

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * A user with a role holding these permissions, and these scopes.
 *
 * @param  list<string>  $permissions
 * @param  list<Model>  $scopes  entities the user works on
 */
function makeUser(array $permissions = [], array $scopes = [], ?string $role = null): User
{
    $user = User::factory()->create();

    if ($role) {
        $user->assignRole(Role::findOrCreate($role, 'web'));
    }

    if ($permissions) {
        $roleModel = Role::create(['name' => 'role-'.$user->id, 'guard_name' => 'web']);
        $roleModel->givePermissionTo($permissions);
        $user->assignRole($roleModel);
    }

    foreach ($scopes as $entity) {
        UserScope::create(['user_id' => $user->id, 'scopeable_type' => $entity->getMorphClass(), 'scopeable_id' => $entity->getKey()]);
    }

    return $user->fresh();
}

function superAdmin(): User
{
    return makeUser(role: config('filament-shield.super_admin.name'));
}

/** Faculty-like test entity ("test.parent"). */
function faculty(string $name = 'Engineering'): FixtureParent
{
    return FixtureParent::create(['name' => $name]);
}

/** Department-like test entity ("test.child") under a faculty. */
function department(FixtureParent $faculty, string $code): FixtureChild
{
    return FixtureChild::create(['parent_id' => $faculty->id, 'name' => $code, 'code' => $code]);
}
