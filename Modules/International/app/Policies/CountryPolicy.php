<?php

declare(strict_types=1);

namespace Modules\International\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\International\Models\Country;
use Platform\Policies\ScopedPolicy;

/** Country: by permission and scope (Shield, Scope engine). */
class CountryPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Country');
    }

    public function view(AuthUser $authUser, Country $country): bool
    {
        return $this->allows($authUser, 'View:Country', $country);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Country');
    }

    public function update(AuthUser $authUser, Country $country): bool
    {
        return $this->allows($authUser, 'Update:Country', $country);
    }

    public function delete(AuthUser $authUser, Country $country): bool
    {
        return $this->allows($authUser, 'Delete:Country', $country);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Country');
    }

    public function restore(AuthUser $authUser, Country $country): bool
    {
        return $this->allows($authUser, 'Restore:Country', $country);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Country');
    }

    public function forceDelete(AuthUser $authUser, Country $country): bool
    {
        return $this->allows($authUser, 'ForceDelete:Country', $country);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Country');
    }
}
