<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Policies\ScopedPolicy;
use Spatie\Permission\Models\Role;

class RolePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'View:Role', $role);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Update:Role', $role);
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Delete:Role', $role);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Role');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Restore:Role', $role);
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'ForceDelete:Role', $role);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Replicate:Role', $role);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Role');
    }
}
