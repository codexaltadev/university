<?php

declare(strict_types=1);

namespace Modules\International\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\International\Models\Conference;
use Platform\Policies\ScopedPolicy;

/** Conference: by permission and scope (Shield, Scope engine). */
class ConferencePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Conference');
    }

    public function view(AuthUser $authUser, Conference $conference): bool
    {
        return $this->allows($authUser, 'View:Conference', $conference);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Conference');
    }

    public function update(AuthUser $authUser, Conference $conference): bool
    {
        return $this->allows($authUser, 'Update:Conference', $conference);
    }

    public function delete(AuthUser $authUser, Conference $conference): bool
    {
        return $this->allows($authUser, 'Delete:Conference', $conference);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Conference');
    }

    public function restore(AuthUser $authUser, Conference $conference): bool
    {
        return $this->allows($authUser, 'Restore:Conference', $conference);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Conference');
    }

    public function forceDelete(AuthUser $authUser, Conference $conference): bool
    {
        return $this->allows($authUser, 'ForceDelete:Conference', $conference);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Conference');
    }
}
