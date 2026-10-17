<?php

declare(strict_types=1);

namespace Modules\Partners\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Partners\Models\Partner;
use Platform\Policies\ScopedPolicy;

/** Partner: by permission and scope (Shield, Scope engine). */
class PartnerPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Partner');
    }

    public function view(AuthUser $authUser, Partner $partner): bool
    {
        return $this->allows($authUser, 'View:Partner', $partner);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Partner');
    }

    public function update(AuthUser $authUser, Partner $partner): bool
    {
        return $this->allows($authUser, 'Update:Partner', $partner);
    }

    public function delete(AuthUser $authUser, Partner $partner): bool
    {
        return $this->allows($authUser, 'Delete:Partner', $partner);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Partner');
    }

    public function restore(AuthUser $authUser, Partner $partner): bool
    {
        return $this->allows($authUser, 'Restore:Partner', $partner);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Partner');
    }

    public function forceDelete(AuthUser $authUser, Partner $partner): bool
    {
        return $this->allows($authUser, 'ForceDelete:Partner', $partner);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Partner');
    }
}
