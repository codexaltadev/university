<?php

declare(strict_types=1);

namespace Modules\Committee\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Committee\Models\Committee;
use Platform\Policies\ScopedPolicy;

/** Committees: by permission and scope — an entity user manages the committees shown in their entities. */
class CommitteePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Committee');
    }

    public function view(AuthUser $authUser, Committee $committee): bool
    {
        return $this->allows($authUser, 'View:Committee', $committee);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Committee');
    }

    public function update(AuthUser $authUser, Committee $committee): bool
    {
        return $this->allows($authUser, 'Update:Committee', $committee);
    }

    public function delete(AuthUser $authUser, Committee $committee): bool
    {
        return $this->allows($authUser, 'Delete:Committee', $committee);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Committee');
    }

    public function restore(AuthUser $authUser, Committee $committee): bool
    {
        return $this->allows($authUser, 'Restore:Committee', $committee);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Committee');
    }

    public function forceDelete(AuthUser $authUser, Committee $committee): bool
    {
        return $this->allows($authUser, 'ForceDelete:Committee', $committee);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Committee');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Committee');
    }
}
