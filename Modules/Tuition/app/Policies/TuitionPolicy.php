<?php

declare(strict_types=1);

namespace Modules\Tuition\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Tuition\Models\Tuition;
use Platform\Policies\ScopedPolicy;

class TuitionPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Tuition');
    }

    public function view(AuthUser $authUser, Tuition $tuition): bool
    {
        return $this->allows($authUser, 'View:Tuition', $tuition);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Tuition');
    }

    public function update(AuthUser $authUser, Tuition $tuition): bool
    {
        return $this->allows($authUser, 'Update:Tuition', $tuition);
    }

    public function delete(AuthUser $authUser, Tuition $tuition): bool
    {
        return $this->allows($authUser, 'Delete:Tuition', $tuition);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Tuition');
    }

    public function restore(AuthUser $authUser, Tuition $tuition): bool
    {
        return $this->allows($authUser, 'Restore:Tuition', $tuition);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Tuition');
    }

    public function forceDelete(AuthUser $authUser, Tuition $tuition): bool
    {
        return $this->allows($authUser, 'ForceDelete:Tuition', $tuition);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Tuition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Tuition');
    }
}
