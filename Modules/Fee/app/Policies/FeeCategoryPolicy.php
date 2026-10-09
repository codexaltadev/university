<?php

declare(strict_types=1);

namespace Modules\Fee\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Fee\Models\FeeCategory;
use Platform\Policies\ScopedPolicy;

class FeeCategoryPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:FeeCategory');
    }

    public function view(AuthUser $authUser, FeeCategory $feeCategory): bool
    {
        return $this->allows($authUser, 'View:FeeCategory', $feeCategory);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:FeeCategory');
    }

    public function update(AuthUser $authUser, FeeCategory $feeCategory): bool
    {
        return $this->allows($authUser, 'Update:FeeCategory', $feeCategory);
    }

    public function delete(AuthUser $authUser, FeeCategory $feeCategory): bool
    {
        return $this->allows($authUser, 'Delete:FeeCategory', $feeCategory);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:FeeCategory');
    }

    public function restore(AuthUser $authUser, FeeCategory $feeCategory): bool
    {
        return $this->allows($authUser, 'Restore:FeeCategory', $feeCategory);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:FeeCategory');
    }

    public function forceDelete(AuthUser $authUser, FeeCategory $feeCategory): bool
    {
        return $this->allows($authUser, 'ForceDelete:FeeCategory', $feeCategory);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:FeeCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:FeeCategory');
    }
}
