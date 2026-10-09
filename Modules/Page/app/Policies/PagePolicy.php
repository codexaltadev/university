<?php

declare(strict_types=1);

namespace Modules\Page\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Page\Models\Page;
use Platform\Policies\ScopedPolicy;

/** Shield permission, then the page's owner inside the user's scope (HasOwner). */
class PagePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Page');
    }

    public function view(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'View:Page', $page);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Page');
    }

    public function update(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'Update:Page', $page);
    }

    public function delete(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'Delete:Page', $page);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Page');
    }

    public function restore(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'Restore:Page', $page);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Page');
    }

    public function forceDelete(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'ForceDelete:Page', $page);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Page');
    }

    public function replicate(AuthUser $authUser, Page $page): bool
    {
        return $this->allows($authUser, 'Replicate:Page', $page);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Page');
    }
}
