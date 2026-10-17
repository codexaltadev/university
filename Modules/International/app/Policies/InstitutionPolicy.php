<?php

declare(strict_types=1);

namespace Modules\International\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\International\Models\Institution;
use Platform\Policies\ScopedPolicy;

/** Institution: by permission and scope (Shield, Scope engine). */
class InstitutionPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Institution');
    }

    public function view(AuthUser $authUser, Institution $institution): bool
    {
        return $this->allows($authUser, 'View:Institution', $institution);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Institution');
    }

    public function update(AuthUser $authUser, Institution $institution): bool
    {
        return $this->allows($authUser, 'Update:Institution', $institution);
    }

    public function delete(AuthUser $authUser, Institution $institution): bool
    {
        return $this->allows($authUser, 'Delete:Institution', $institution);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Institution');
    }

    public function restore(AuthUser $authUser, Institution $institution): bool
    {
        return $this->allows($authUser, 'Restore:Institution', $institution);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Institution');
    }

    public function forceDelete(AuthUser $authUser, Institution $institution): bool
    {
        return $this->allows($authUser, 'ForceDelete:Institution', $institution);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Institution');
    }
}
