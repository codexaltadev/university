<?php

declare(strict_types=1);

namespace Modules\Waiver\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Waiver\Models\WaiverCategory;
use Platform\Policies\ScopedPolicy;

class WaiverCategoryPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:WaiverCategory');
    }

    public function view(AuthUser $authUser, WaiverCategory $waiverCategory): bool
    {
        return $this->allows($authUser, 'View:WaiverCategory', $waiverCategory);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:WaiverCategory');
    }

    public function update(AuthUser $authUser, WaiverCategory $waiverCategory): bool
    {
        return $this->allows($authUser, 'Update:WaiverCategory', $waiverCategory);
    }

    public function delete(AuthUser $authUser, WaiverCategory $waiverCategory): bool
    {
        return $this->allows($authUser, 'Delete:WaiverCategory', $waiverCategory);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:WaiverCategory');
    }

    public function restore(AuthUser $authUser, WaiverCategory $waiverCategory): bool
    {
        return $this->allows($authUser, 'Restore:WaiverCategory', $waiverCategory);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:WaiverCategory');
    }

    public function forceDelete(AuthUser $authUser, WaiverCategory $waiverCategory): bool
    {
        return $this->allows($authUser, 'ForceDelete:WaiverCategory', $waiverCategory);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:WaiverCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:WaiverCategory');
    }
}
