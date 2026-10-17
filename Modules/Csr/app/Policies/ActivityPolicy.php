<?php

declare(strict_types=1);

namespace Modules\Csr\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Csr\Models\Activity;
use Platform\Policies\ScopedPolicy;

/** Activity: by permission and scope (Shield, Scope engine). */
class ActivityPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Activity');
    }

    public function view(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'View:Activity', $activity);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Activity');
    }

    public function update(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'Update:Activity', $activity);
    }

    public function delete(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'Delete:Activity', $activity);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Activity');
    }

    public function restore(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'Restore:Activity', $activity);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Activity');
    }

    public function forceDelete(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'ForceDelete:Activity', $activity);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Activity');
    }
}
