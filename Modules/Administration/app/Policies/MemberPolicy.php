<?php

declare(strict_types=1);

namespace Modules\Administration\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Administration\Models\Member;
use Platform\Policies\ScopedPolicy;

/** Members: by permission; an Office user only those of their office (BRL-ADMN-02, scope by primary office). */
class MemberPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Member');
    }

    public function view(AuthUser $authUser, Member $member): bool
    {
        return $this->allows($authUser, 'View:Member', $member);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Member');
    }

    public function update(AuthUser $authUser, Member $member): bool
    {
        return $this->allows($authUser, 'Update:Member', $member);
    }

    public function delete(AuthUser $authUser, Member $member): bool
    {
        return $this->allows($authUser, 'Delete:Member', $member);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Member');
    }

    public function restore(AuthUser $authUser, Member $member): bool
    {
        return $this->allows($authUser, 'Restore:Member', $member);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Member');
    }

    public function forceDelete(AuthUser $authUser, Member $member): bool
    {
        return $this->allows($authUser, 'ForceDelete:Member', $member);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Member');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Member');
    }
}
