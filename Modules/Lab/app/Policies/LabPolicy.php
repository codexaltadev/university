<?php

declare(strict_types=1);

namespace Modules\Lab\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Lab\Models\Lab;
use Platform\Policies\ScopedPolicy;

/** Lab: by permission and scope (Shield, Scope engine). */
class LabPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Lab');
    }

    public function view(AuthUser $authUser, Lab $lab): bool
    {
        return $this->allows($authUser, 'View:Lab', $lab);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Lab');
    }

    public function update(AuthUser $authUser, Lab $lab): bool
    {
        return $this->allows($authUser, 'Update:Lab', $lab);
    }

    public function delete(AuthUser $authUser, Lab $lab): bool
    {
        return $this->allows($authUser, 'Delete:Lab', $lab);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Lab');
    }

    public function restore(AuthUser $authUser, Lab $lab): bool
    {
        return $this->allows($authUser, 'Restore:Lab', $lab);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Lab');
    }

    public function forceDelete(AuthUser $authUser, Lab $lab): bool
    {
        return $this->allows($authUser, 'ForceDelete:Lab', $lab);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Lab');
    }
}
