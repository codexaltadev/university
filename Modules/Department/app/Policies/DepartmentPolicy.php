<?php

declare(strict_types=1);

namespace Modules\Department\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Department\Models\Department;
use Platform\Policies\ScopedPolicy;

class DepartmentPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Department');
    }

    public function view(AuthUser $authUser, Department $department): bool
    {
        return $this->allows($authUser, 'View:Department', $department);
    }

    /** BRL-DEPT-03: Faculty users only with this permission (not given by default). */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Department');
    }

    public function update(AuthUser $authUser, Department $department): bool
    {
        return $this->allows($authUser, 'Update:Department', $department);
    }

    public function delete(AuthUser $authUser, Department $department): bool
    {
        return $this->allows($authUser, 'Delete:Department', $department);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Department');
    }

    public function restore(AuthUser $authUser, Department $department): bool
    {
        return $this->allows($authUser, 'Restore:Department', $department);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Department');
    }

    public function forceDelete(AuthUser $authUser, Department $department): bool
    {
        return $this->allows($authUser, 'ForceDelete:Department', $department);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Department');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Department');
    }
}
