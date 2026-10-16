<?php

declare(strict_types=1);

namespace Modules\Events\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Events\Models\Event;
use Platform\Policies\ScopedPolicy;

/** Events: by permission and scope — an entity user manages the events shown in their entities. */
class EventPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Event');
    }

    public function view(AuthUser $authUser, Event $event): bool
    {
        return $this->allows($authUser, 'View:Event', $event);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Event');
    }

    public function update(AuthUser $authUser, Event $event): bool
    {
        return $this->allows($authUser, 'Update:Event', $event);
    }

    public function delete(AuthUser $authUser, Event $event): bool
    {
        return $this->allows($authUser, 'Delete:Event', $event);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Event');
    }

    public function restore(AuthUser $authUser, Event $event): bool
    {
        return $this->allows($authUser, 'Restore:Event', $event);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Event');
    }

    public function forceDelete(AuthUser $authUser, Event $event): bool
    {
        return $this->allows($authUser, 'ForceDelete:Event', $event);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Event');
    }
}
