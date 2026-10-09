<?php

declare(strict_types=1);

namespace Modules\Program\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Program\Models\Program;
use Platform\Policies\ScopedPolicy;

class ProgramPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Program');
    }

    public function view(AuthUser $authUser, Program $program): bool
    {
        return $this->allows($authUser, 'View:Program', $program);
    }

    /** Faculty / Department users only with this permission (not given by default). */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Program');
    }

    public function update(AuthUser $authUser, Program $program): bool
    {
        return $this->allows($authUser, 'Update:Program', $program);
    }

    public function delete(AuthUser $authUser, Program $program): bool
    {
        return $this->allows($authUser, 'Delete:Program', $program);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Program');
    }

    public function restore(AuthUser $authUser, Program $program): bool
    {
        return $this->allows($authUser, 'Restore:Program', $program);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Program');
    }

    public function forceDelete(AuthUser $authUser, Program $program): bool
    {
        return $this->allows($authUser, 'ForceDelete:Program', $program);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Program');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Program');
    }
}
