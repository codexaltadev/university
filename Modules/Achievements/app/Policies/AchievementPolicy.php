<?php

declare(strict_types=1);

namespace Modules\Achievements\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Achievements\Models\Achievement;
use Platform\Policies\ScopedPolicy;

/** Achievements: by permission and scope — an entity user manages the achievements shown in their entities. */
class AchievementPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Achievement');
    }

    public function view(AuthUser $authUser, Achievement $achievement): bool
    {
        return $this->allows($authUser, 'View:Achievement', $achievement);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Achievement');
    }

    public function update(AuthUser $authUser, Achievement $achievement): bool
    {
        return $this->allows($authUser, 'Update:Achievement', $achievement);
    }

    public function delete(AuthUser $authUser, Achievement $achievement): bool
    {
        return $this->allows($authUser, 'Delete:Achievement', $achievement);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Achievement');
    }

    public function restore(AuthUser $authUser, Achievement $achievement): bool
    {
        return $this->allows($authUser, 'Restore:Achievement', $achievement);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Achievement');
    }

    public function forceDelete(AuthUser $authUser, Achievement $achievement): bool
    {
        return $this->allows($authUser, 'ForceDelete:Achievement', $achievement);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Achievement');
    }
}
