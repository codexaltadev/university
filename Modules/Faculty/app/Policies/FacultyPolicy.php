<?php

declare(strict_types=1);

namespace Modules\Faculty\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Faculty\Models\Faculty;
use Platform\Policies\ScopedPolicy;

class FacultyPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Faculty');
    }

    public function view(AuthUser $authUser, Faculty $faculty): bool
    {
        return $this->allows($authUser, 'View:Faculty', $faculty);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Faculty');
    }

    public function update(AuthUser $authUser, Faculty $faculty): bool
    {
        return $this->allows($authUser, 'Update:Faculty', $faculty);
    }

    public function delete(AuthUser $authUser, Faculty $faculty): bool
    {
        return $this->allows($authUser, 'Delete:Faculty', $faculty);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Faculty');
    }

    public function restore(AuthUser $authUser, Faculty $faculty): bool
    {
        return $this->allows($authUser, 'Restore:Faculty', $faculty);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Faculty');
    }

    public function forceDelete(AuthUser $authUser, Faculty $faculty): bool
    {
        return $this->allows($authUser, 'ForceDelete:Faculty', $faculty);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Faculty');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Faculty');
    }
}
