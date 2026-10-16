<?php

declare(strict_types=1);

namespace Modules\Links\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Links\Models\Link;
use Platform\Policies\ScopedPolicy;

/** Links: by permission and scope — an entity user manages the links shown in their entities. */
class LinkPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Link');
    }

    public function view(AuthUser $authUser, Link $link): bool
    {
        return $this->allows($authUser, 'View:Link', $link);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Link');
    }

    public function update(AuthUser $authUser, Link $link): bool
    {
        return $this->allows($authUser, 'Update:Link', $link);
    }

    public function delete(AuthUser $authUser, Link $link): bool
    {
        return $this->allows($authUser, 'Delete:Link', $link);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Link');
    }

    public function restore(AuthUser $authUser, Link $link): bool
    {
        return $this->allows($authUser, 'Restore:Link', $link);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Link');
    }

    public function forceDelete(AuthUser $authUser, Link $link): bool
    {
        return $this->allows($authUser, 'ForceDelete:Link', $link);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Link');
    }
}
