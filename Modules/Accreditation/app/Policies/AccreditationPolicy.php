<?php

declare(strict_types=1);

namespace Modules\Accreditation\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Accreditation\Models\Accreditation;
use Platform\Policies\ScopedPolicy;

/** Accreditation: by permission and scope (Shield, Scope engine). */
class AccreditationPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Accreditation');
    }

    public function view(AuthUser $authUser, Accreditation $accreditation): bool
    {
        return $this->allows($authUser, 'View:Accreditation', $accreditation);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Accreditation');
    }

    public function update(AuthUser $authUser, Accreditation $accreditation): bool
    {
        return $this->allows($authUser, 'Update:Accreditation', $accreditation);
    }

    public function delete(AuthUser $authUser, Accreditation $accreditation): bool
    {
        return $this->allows($authUser, 'Delete:Accreditation', $accreditation);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Accreditation');
    }

    public function restore(AuthUser $authUser, Accreditation $accreditation): bool
    {
        return $this->allows($authUser, 'Restore:Accreditation', $accreditation);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Accreditation');
    }

    public function forceDelete(AuthUser $authUser, Accreditation $accreditation): bool
    {
        return $this->allows($authUser, 'ForceDelete:Accreditation', $accreditation);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Accreditation');
    }
}
