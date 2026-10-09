<?php

declare(strict_types=1);

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Media\MediaItem;
use Platform\Policies\ScopedPolicy;

/**
 * SRS C-07: view (ViewAny / View), upload (Create), update, delete; each record
 * also has to be visible to the user (own scope, own upload or shared — FR-MEDIA-14).
 */
class MediaItemPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:MediaItem');
    }

    public function view(AuthUser $authUser, MediaItem $item): bool
    {
        return $this->allows($authUser, 'View:MediaItem') && $this->visible($authUser, $item);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:MediaItem');
    }

    public function update(AuthUser $authUser, MediaItem $item): bool
    {
        return $this->allows($authUser, 'Update:MediaItem') && $this->visible($authUser, $item);
    }

    public function delete(AuthUser $authUser, MediaItem $item): bool
    {
        return $this->allows($authUser, 'Delete:MediaItem') && $this->visible($authUser, $item);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Delete:MediaItem');
    }

    public function restore(AuthUser $authUser, MediaItem $item): bool
    {
        return $this->allows($authUser, 'Restore:MediaItem') && $this->visible($authUser, $item);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Restore:MediaItem');
    }

    public function forceDelete(AuthUser $authUser, MediaItem $item): bool
    {
        return $this->allows($authUser, 'ForceDelete:MediaItem') && $this->visible($authUser, $item);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDelete:MediaItem');
    }

    protected function visible(AuthUser $authUser, MediaItem $item): bool
    {
        return $authUser instanceof User && $item->isVisibleTo($authUser);
    }
}
