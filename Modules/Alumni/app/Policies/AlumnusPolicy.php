<?php

declare(strict_types=1);

namespace Modules\Alumni\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Alumni\Models\Alumnus;
use Platform\Policies\ScopedPolicy;

/** Alumni: by permission and scope — a Department / Program user manages their own graduates. */
class AlumnusPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Alumnus');
    }

    public function view(AuthUser $authUser, Alumnus $alumnus): bool
    {
        return $this->allows($authUser, 'View:Alumnus', $alumnus);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Alumnus');
    }

    public function update(AuthUser $authUser, Alumnus $alumnus): bool
    {
        return $this->allows($authUser, 'Update:Alumnus', $alumnus);
    }

    public function delete(AuthUser $authUser, Alumnus $alumnus): bool
    {
        return $this->allows($authUser, 'Delete:Alumnus', $alumnus);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Alumnus');
    }

    public function restore(AuthUser $authUser, Alumnus $alumnus): bool
    {
        return $this->allows($authUser, 'Restore:Alumnus', $alumnus);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Alumnus');
    }

    public function forceDelete(AuthUser $authUser, Alumnus $alumnus): bool
    {
        return $this->allows($authUser, 'ForceDelete:Alumnus', $alumnus);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Alumnus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Alumnus');
    }
}
