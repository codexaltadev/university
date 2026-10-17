<?php

declare(strict_types=1);

namespace Modules\Facilities\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Facilities\Models\Facility;
use Platform\Policies\ScopedPolicy;

/** Facility: by permission and scope (Shield, Scope engine). */
class FacilityPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Facility');
    }

    public function view(AuthUser $authUser, Facility $facility): bool
    {
        return $this->allows($authUser, 'View:Facility', $facility);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Facility');
    }

    public function update(AuthUser $authUser, Facility $facility): bool
    {
        return $this->allows($authUser, 'Update:Facility', $facility);
    }

    public function delete(AuthUser $authUser, Facility $facility): bool
    {
        return $this->allows($authUser, 'Delete:Facility', $facility);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Facility');
    }

    public function restore(AuthUser $authUser, Facility $facility): bool
    {
        return $this->allows($authUser, 'Restore:Facility', $facility);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Facility');
    }

    public function forceDelete(AuthUser $authUser, Facility $facility): bool
    {
        return $this->allows($authUser, 'ForceDelete:Facility', $facility);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Facility');
    }
}
