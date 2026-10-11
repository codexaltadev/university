<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\AdmissionStep;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class AdmissionStepPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdmissionStep');
    }

    public function view(AuthUser $authUser, AdmissionStep $admissionStep): bool
    {
        return $this->allows($authUser, 'View:AdmissionStep', $admissionStep);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdmissionStep');
    }

    public function update(AuthUser $authUser, AdmissionStep $admissionStep): bool
    {
        return $this->allows($authUser, 'Update:AdmissionStep', $admissionStep);
    }

    public function delete(AuthUser $authUser, AdmissionStep $admissionStep): bool
    {
        return $this->allows($authUser, 'Delete:AdmissionStep', $admissionStep);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdmissionStep');
    }

    public function restore(AuthUser $authUser, AdmissionStep $admissionStep): bool
    {
        return $this->allows($authUser, 'Restore:AdmissionStep', $admissionStep);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:AdmissionStep');
    }

    public function forceDelete(AuthUser $authUser, AdmissionStep $admissionStep): bool
    {
        return $this->allows($authUser, 'ForceDelete:AdmissionStep', $admissionStep);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:AdmissionStep');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdmissionStep');
    }
}
