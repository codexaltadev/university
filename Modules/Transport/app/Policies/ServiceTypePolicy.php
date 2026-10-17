<?php

declare(strict_types=1);

namespace Modules\Transport\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Transport\Models\ServiceType;
use Platform\Policies\ScopedPolicy;

/** Transport service types: by permission (Shield) — university-wide data, no entity scope. */
class ServiceTypePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:ServiceType');
    }

    public function view(AuthUser $authUser, ServiceType $serviceType): bool
    {
        return $this->allows($authUser, 'View:ServiceType', $serviceType);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:ServiceType');
    }

    public function update(AuthUser $authUser, ServiceType $serviceType): bool
    {
        return $this->allows($authUser, 'Update:ServiceType', $serviceType);
    }

    public function delete(AuthUser $authUser, ServiceType $serviceType): bool
    {
        return $this->allows($authUser, 'Delete:ServiceType', $serviceType);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:ServiceType');
    }

    public function restore(AuthUser $authUser, ServiceType $serviceType): bool
    {
        return $this->allows($authUser, 'Restore:ServiceType', $serviceType);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:ServiceType');
    }

    public function forceDelete(AuthUser $authUser, ServiceType $serviceType): bool
    {
        return $this->allows($authUser, 'ForceDelete:ServiceType', $serviceType);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:ServiceType');
    }
}
