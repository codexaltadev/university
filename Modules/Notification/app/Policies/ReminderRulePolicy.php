<?php

declare(strict_types=1);

namespace Modules\Notification\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Notification\Models\ReminderRule;
use Platform\Policies\ScopedPolicy;

class ReminderRulePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:ReminderRule');
    }

    public function view(AuthUser $authUser, ReminderRule $rule): bool
    {
        return $this->allows($authUser, 'View:ReminderRule', $rule);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:ReminderRule');
    }

    public function update(AuthUser $authUser, ReminderRule $rule): bool
    {
        return $this->allows($authUser, 'Update:ReminderRule', $rule);
    }

    public function delete(AuthUser $authUser, ReminderRule $rule): bool
    {
        return $this->allows($authUser, 'Delete:ReminderRule', $rule);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:ReminderRule');
    }

    public function restore(AuthUser $authUser, ReminderRule $rule): bool
    {
        return $this->allows($authUser, 'Restore:ReminderRule', $rule);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:ReminderRule');
    }

    public function forceDelete(AuthUser $authUser, ReminderRule $rule): bool
    {
        return $this->allows($authUser, 'ForceDelete:ReminderRule', $rule);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:ReminderRule');
    }
}
