<?php

declare(strict_types=1);

namespace Modules\Gallery\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Gallery\Models\Album;
use Platform\Policies\ScopedPolicy;

/** Albums: by permission and scope — an entity user manages the albums shown in their entities. */
class AlbumPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Album');
    }

    public function view(AuthUser $authUser, Album $album): bool
    {
        return $this->allows($authUser, 'View:Album', $album);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Album');
    }

    public function update(AuthUser $authUser, Album $album): bool
    {
        return $this->allows($authUser, 'Update:Album', $album);
    }

    public function delete(AuthUser $authUser, Album $album): bool
    {
        return $this->allows($authUser, 'Delete:Album', $album);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Album');
    }

    public function restore(AuthUser $authUser, Album $album): bool
    {
        return $this->allows($authUser, 'Restore:Album', $album);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Album');
    }

    public function forceDelete(AuthUser $authUser, Album $album): bool
    {
        return $this->allows($authUser, 'ForceDelete:Album', $album);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Album');
    }
}
