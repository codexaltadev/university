<?php

declare(strict_types=1);

namespace Modules\Newsletter\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Newsletter\Models\Campaign;
use Platform\Policies\ScopedPolicy;

/** Campaign: by permission and scope (Shield, Scope engine). */
class CampaignPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Campaign');
    }

    public function view(AuthUser $authUser, Campaign $campaign): bool
    {
        return $this->allows($authUser, 'View:Campaign', $campaign);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Campaign');
    }

    public function update(AuthUser $authUser, Campaign $campaign): bool
    {
        return $this->allows($authUser, 'Update:Campaign', $campaign);
    }

    public function delete(AuthUser $authUser, Campaign $campaign): bool
    {
        return $this->allows($authUser, 'Delete:Campaign', $campaign);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Campaign');
    }

    public function restore(AuthUser $authUser, Campaign $campaign): bool
    {
        return $this->allows($authUser, 'Restore:Campaign', $campaign);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Campaign');
    }

    public function forceDelete(AuthUser $authUser, Campaign $campaign): bool
    {
        return $this->allows($authUser, 'ForceDelete:Campaign', $campaign);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Campaign');
    }
}
