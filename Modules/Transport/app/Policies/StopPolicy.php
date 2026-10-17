<?php

declare(strict_types=1);

namespace Modules\Transport\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Transport\Models\Stop;
use Platform\Policies\ScopedPolicy;

/** Bus stops: by permission (Shield) — university-wide data, no entity scope. */
class StopPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Stop');
    }

    public function view(AuthUser $authUser, Stop $stop): bool
    {
        return $this->allows($authUser, 'View:Stop', $stop);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Stop');
    }

    public function update(AuthUser $authUser, Stop $stop): bool
    {
        return $this->allows($authUser, 'Update:Stop', $stop);
    }

    public function delete(AuthUser $authUser, Stop $stop): bool
    {
        return $this->allows($authUser, 'Delete:Stop', $stop);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Stop');
    }

    public function restore(AuthUser $authUser, Stop $stop): bool
    {
        return $this->allows($authUser, 'Restore:Stop', $stop);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Stop');
    }

    public function forceDelete(AuthUser $authUser, Stop $stop): bool
    {
        return $this->allows($authUser, 'ForceDelete:Stop', $stop);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Stop');
    }
}
