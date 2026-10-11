<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\AdmissionHighlight;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class AdmissionHighlightPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdmissionHighlight');
    }

    public function view(AuthUser $authUser, AdmissionHighlight $admissionHighlight): bool
    {
        return $this->allows($authUser, 'View:AdmissionHighlight', $admissionHighlight);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdmissionHighlight');
    }

    public function update(AuthUser $authUser, AdmissionHighlight $admissionHighlight): bool
    {
        return $this->allows($authUser, 'Update:AdmissionHighlight', $admissionHighlight);
    }

    public function delete(AuthUser $authUser, AdmissionHighlight $admissionHighlight): bool
    {
        return $this->allows($authUser, 'Delete:AdmissionHighlight', $admissionHighlight);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdmissionHighlight');
    }

    public function restore(AuthUser $authUser, AdmissionHighlight $admissionHighlight): bool
    {
        return $this->allows($authUser, 'Restore:AdmissionHighlight', $admissionHighlight);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:AdmissionHighlight');
    }

    public function forceDelete(AuthUser $authUser, AdmissionHighlight $admissionHighlight): bool
    {
        return $this->allows($authUser, 'ForceDelete:AdmissionHighlight', $admissionHighlight);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:AdmissionHighlight');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdmissionHighlight');
    }
}
