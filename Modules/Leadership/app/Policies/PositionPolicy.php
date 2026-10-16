<?php

declare(strict_types=1);

namespace Modules\Leadership\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Leadership\Models\Position;
use Platform\Policies\ScopedPolicy;

/** Leadership positions and their terms: by permission (an Admin by default). */
class PositionPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Position');
    }

    public function view(AuthUser $authUser, Position $position): bool
    {
        return $this->allows($authUser, 'View:Position', $position);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Position');
    }

    public function update(AuthUser $authUser, Position $position): bool
    {
        return $this->allows($authUser, 'Update:Position', $position);
    }

    public function delete(AuthUser $authUser, Position $position): bool
    {
        return $this->allows($authUser, 'Delete:Position', $position);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Position');
    }

    public function restore(AuthUser $authUser, Position $position): bool
    {
        return $this->allows($authUser, 'Restore:Position', $position);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Position');
    }

    public function forceDelete(AuthUser $authUser, Position $position): bool
    {
        return $this->allows($authUser, 'ForceDelete:Position', $position);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Position');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Position');
    }
}
