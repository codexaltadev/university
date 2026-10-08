<?php

namespace Platform\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Platform\Contracts\HasEntityPermissions;
use Platform\Permissions\EntityPermissions;
use Platform\Scope\ScopeResolver;

/**
 * Base for every Shield-generated policy (SAD Part A §7.4).
 *
 * 1. Shield permission        (e.g. Update:Faq)
 * 2. Record inside user scope (Scope engine, P-03)
 * 3. Entity permission        (e.g. Manage:Office#12) for Office / Lab records
 * Super Admin bypasses all steps.
 */
abstract class ScopedPolicy
{
    use HandlesAuthorization;

    protected function allows(Authenticatable $user, string $permission, ?Model $record = null): bool
    {
        if ($user->hasRole(config('filament-shield.super_admin.name'))) {
            return true;
        }

        if (! $user->can($permission)) {
            return false;
        }

        if ($record === null) {
            return true;
        }

        if (! app(ScopeResolver::class)->allows($user, $record)) {
            return false;
        }

        if ($record instanceof HasEntityPermissions) {
            return app(EntityPermissions::class)->userCanAny($user, $record);
        }

        return true;
    }
}
