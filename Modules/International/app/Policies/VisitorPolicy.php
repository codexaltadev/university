<?php

declare(strict_types=1);

namespace Modules\International\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\International\Models\Visitor;
use Platform\Policies\ScopedPolicy;

/** Visitor: by permission and scope (Shield, Scope engine). */
class VisitorPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Visitor');
    }

    public function view(AuthUser $authUser, Visitor $visitor): bool
    {
        return $this->allows($authUser, 'View:Visitor', $visitor);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Visitor');
    }

    public function update(AuthUser $authUser, Visitor $visitor): bool
    {
        return $this->allows($authUser, 'Update:Visitor', $visitor);
    }

    public function delete(AuthUser $authUser, Visitor $visitor): bool
    {
        return $this->allows($authUser, 'Delete:Visitor', $visitor);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Visitor');
    }

    public function restore(AuthUser $authUser, Visitor $visitor): bool
    {
        return $this->allows($authUser, 'Restore:Visitor', $visitor);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Visitor');
    }

    public function forceDelete(AuthUser $authUser, Visitor $visitor): bool
    {
        return $this->allows($authUser, 'ForceDelete:Visitor', $visitor);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Visitor');
    }
}
