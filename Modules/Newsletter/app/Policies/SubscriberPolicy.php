<?php

declare(strict_types=1);

namespace Modules\Newsletter\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Newsletter\Models\Subscriber;
use Platform\Policies\ScopedPolicy;

/** Subscriber: by permission and scope (Shield, Scope engine). */
class SubscriberPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Subscriber');
    }

    public function view(AuthUser $authUser, Subscriber $subscriber): bool
    {
        return $this->allows($authUser, 'View:Subscriber', $subscriber);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Subscriber');
    }

    public function update(AuthUser $authUser, Subscriber $subscriber): bool
    {
        return $this->allows($authUser, 'Update:Subscriber', $subscriber);
    }

    public function delete(AuthUser $authUser, Subscriber $subscriber): bool
    {
        return $this->allows($authUser, 'Delete:Subscriber', $subscriber);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Subscriber');
    }

    public function restore(AuthUser $authUser, Subscriber $subscriber): bool
    {
        return $this->allows($authUser, 'Restore:Subscriber', $subscriber);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Subscriber');
    }

    public function forceDelete(AuthUser $authUser, Subscriber $subscriber): bool
    {
        return $this->allows($authUser, 'ForceDelete:Subscriber', $subscriber);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Subscriber');
    }
}
