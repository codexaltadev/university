<?php

declare(strict_types=1);

namespace ModulesTransportPolicies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Policies\ScopedPolicy;

/** s: by permission and scope — an entity user manages the xs shown in their entities. */
class Policy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:');
    }

    public function view(AuthUser $authUser, $x): bool
    {
        return $this->allows($authUser, 'View:', $x);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:');
    }

    public function update(AuthUser $authUser, $x): bool
    {
        return $this->allows($authUser, 'Update:', $x);
    }

    public function delete(AuthUser $authUser, $x): bool
    {
        return $this->allows($authUser, 'Delete:', $x);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:');
    }

    public function restore(AuthUser $authUser, $x): bool
    {
        return $this->allows($authUser, 'Restore:', $x);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:');
    }

    public function forceDelete(AuthUser $authUser, $x): bool
    {
        return $this->allows($authUser, 'ForceDelete:', $x);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:');
    }
}
