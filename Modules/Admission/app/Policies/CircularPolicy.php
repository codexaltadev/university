<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\Circular;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class CircularPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Circular');
    }

    public function view(AuthUser $authUser, Circular $circular): bool
    {
        return $this->allows($authUser, 'View:Circular', $circular);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Circular');
    }

    public function update(AuthUser $authUser, Circular $circular): bool
    {
        return $this->allows($authUser, 'Update:Circular', $circular);
    }

    public function delete(AuthUser $authUser, Circular $circular): bool
    {
        return $this->allows($authUser, 'Delete:Circular', $circular);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Circular');
    }

    public function restore(AuthUser $authUser, Circular $circular): bool
    {
        return $this->allows($authUser, 'Restore:Circular', $circular);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Circular');
    }

    public function forceDelete(AuthUser $authUser, Circular $circular): bool
    {
        return $this->allows($authUser, 'ForceDelete:Circular', $circular);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Circular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Circular');
    }
}
