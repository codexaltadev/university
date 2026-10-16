<?php

declare(strict_types=1);

namespace Modules\Notice\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Notice\Models\Notice;
use Platform\Policies\ScopedPolicy;

/** Notices: by permission and scope — an entity user manages the notices shown in their entities. */
class NoticePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Notice');
    }

    public function view(AuthUser $authUser, Notice $notice): bool
    {
        return $this->allows($authUser, 'View:Notice', $notice);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Notice');
    }

    public function update(AuthUser $authUser, Notice $notice): bool
    {
        return $this->allows($authUser, 'Update:Notice', $notice);
    }

    public function delete(AuthUser $authUser, Notice $notice): bool
    {
        return $this->allows($authUser, 'Delete:Notice', $notice);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Notice');
    }

    public function restore(AuthUser $authUser, Notice $notice): bool
    {
        return $this->allows($authUser, 'Restore:Notice', $notice);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Notice');
    }

    public function forceDelete(AuthUser $authUser, Notice $notice): bool
    {
        return $this->allows($authUser, 'ForceDelete:Notice', $notice);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Notice');
    }
}
