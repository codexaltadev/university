<?php

declare(strict_types=1);

namespace Modules\Office\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Office\Models\Office;
use Platform\Policies\ScopedPolicy;

class OfficePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Office');
    }

    public function view(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'View:Office', $office);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Office');
    }

    public function update(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'Update:Office', $office);
    }

    public function delete(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'Delete:Office', $office);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Office');
    }

    public function restore(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'Restore:Office', $office);
    }

    public function forceDelete(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'ForceDelete:Office', $office);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Office');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Office');
    }

    public function replicate(AuthUser $authUser, Office $office): bool
    {
        return $this->allows($authUser, 'Replicate:Office', $office);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Office');
    }
}
