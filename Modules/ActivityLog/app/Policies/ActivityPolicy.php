<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Activity\Activity;
use Platform\Policies\ScopedPolicy;

/** The activity log can be read, never changed or deleted by anyone (FR-LOG-09). */
class ActivityPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Activity');
    }

    public function view(AuthUser $authUser, Activity $activity): bool
    {
        return $this->allows($authUser, 'View:Activity');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, Activity $activity): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, Activity $activity): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Activity $activity): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Activity $activity): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }
}
