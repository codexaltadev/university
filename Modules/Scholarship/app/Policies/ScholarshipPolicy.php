<?php

declare(strict_types=1);

namespace Modules\Scholarship\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Scholarship\Models\Scholarship;
use Platform\Policies\ScopedPolicy;

/** Scholarships have no scope: whoever has the permission (by default the Waiver Office) sees them all. */
class ScholarshipPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Scholarship');
    }

    public function view(AuthUser $authUser, Scholarship $scholarship): bool
    {
        return $this->allows($authUser, 'View:Scholarship', $scholarship);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Scholarship');
    }

    public function update(AuthUser $authUser, Scholarship $scholarship): bool
    {
        return $this->allows($authUser, 'Update:Scholarship', $scholarship);
    }

    /** Not while it has parts (FR-GEN-08). */
    public function delete(AuthUser $authUser, Scholarship $scholarship): bool
    {
        return $this->allows($authUser, 'Delete:Scholarship', $scholarship) && ! $scholarship->parts()->exists();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Scholarship');
    }

    public function restore(AuthUser $authUser, Scholarship $scholarship): bool
    {
        return $this->allows($authUser, 'Restore:Scholarship', $scholarship);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Scholarship');
    }

    public function forceDelete(AuthUser $authUser, Scholarship $scholarship): bool
    {
        return $this->allows($authUser, 'ForceDelete:Scholarship', $scholarship) && ! $scholarship->parts()->withTrashed()->exists();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Scholarship');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Scholarship');
    }
}
