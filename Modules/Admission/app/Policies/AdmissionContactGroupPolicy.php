<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\AdmissionContactGroup;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class AdmissionContactGroupPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdmissionContactGroup');
    }

    public function view(AuthUser $authUser, AdmissionContactGroup $admissionContactGroup): bool
    {
        return $this->allows($authUser, 'View:AdmissionContactGroup', $admissionContactGroup);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdmissionContactGroup');
    }

    public function update(AuthUser $authUser, AdmissionContactGroup $admissionContactGroup): bool
    {
        return $this->allows($authUser, 'Update:AdmissionContactGroup', $admissionContactGroup);
    }

    public function delete(AuthUser $authUser, AdmissionContactGroup $admissionContactGroup): bool
    {
        return $this->allows($authUser, 'Delete:AdmissionContactGroup', $admissionContactGroup);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdmissionContactGroup');
    }

    public function restore(AuthUser $authUser, AdmissionContactGroup $admissionContactGroup): bool
    {
        return $this->allows($authUser, 'Restore:AdmissionContactGroup', $admissionContactGroup);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:AdmissionContactGroup');
    }

    public function forceDelete(AuthUser $authUser, AdmissionContactGroup $admissionContactGroup): bool
    {
        return $this->allows($authUser, 'ForceDelete:AdmissionContactGroup', $admissionContactGroup);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:AdmissionContactGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdmissionContactGroup');
    }
}
