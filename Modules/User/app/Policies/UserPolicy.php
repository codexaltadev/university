<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Policies\ScopedPolicy;

/**
 * Users (C-02). On top of Shield permissions:
 * - only a Super Admin may change or remove a Super Admin,
 * - nobody deletes their own account.
 */
class UserPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:User');
    }

    public function view(AuthUser $authUser, User $user): bool
    {
        return $this->allows($authUser, 'View:User', $user);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:User');
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        return $this->protects($authUser, $user) && $this->allows($authUser, 'Update:User', $user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        return ! $authUser->is($user) && $this->protects($authUser, $user) && $this->allows($authUser, 'Delete:User', $user);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:User');
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $this->allows($authUser, 'Restore:User', $user);
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        return ! $authUser->is($user) && $this->protects($authUser, $user) && $this->allows($authUser, 'ForceDelete:User', $user);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:User');
    }

    public function replicate(AuthUser $authUser, User $user): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }

    /** A Super Admin account is only changed by a Super Admin. */
    protected function protects(AuthUser $authUser, User $user): bool
    {
        return ! $user->isSuperAdmin() || $authUser->isSuperAdmin();
    }
}
