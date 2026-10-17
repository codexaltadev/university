<?php

declare(strict_types=1);

namespace Modules\FacultyMember\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Policies\ScopedPolicy;

/**
 * Teachers: by permission and scope. The faculty site owns them, so nobody creates, deletes or
 * restores them here (BRL-FM-03); "update" is only the local contact switches (FR-FM-11).
 */
class FacultyMemberPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:FacultyMember');
    }

    public function view(AuthUser $authUser, FacultyMember $facultyMember): bool
    {
        return $this->allows($authUser, 'View:FacultyMember', $facultyMember);
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, FacultyMember $facultyMember): bool
    {
        return $this->allows($authUser, 'Update:FacultyMember', $facultyMember);
    }

    public function delete(AuthUser $authUser, FacultyMember $facultyMember): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, FacultyMember $facultyMember): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, FacultyMember $facultyMember): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:FacultyMember');
    }

    /** "Sync now" (FR-FM-08). */
    public function sync(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Sync:FacultyMember');
    }
}
