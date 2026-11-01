<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\AdmissionSeat;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class AdmissionSeatPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdmissionSeat');
    }

    public function view(AuthUser $authUser, AdmissionSeat $admissionSeat): bool
    {
        return $this->allows($authUser, 'View:AdmissionSeat', $admissionSeat);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdmissionSeat');
    }

    public function update(AuthUser $authUser, AdmissionSeat $admissionSeat): bool
    {
        return $this->allows($authUser, 'Update:AdmissionSeat', $admissionSeat);
    }

    public function delete(AuthUser $authUser, AdmissionSeat $admissionSeat): bool
    {
        return $this->allows($authUser, 'Delete:AdmissionSeat', $admissionSeat);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdmissionSeat');
    }

    public function restore(AuthUser $authUser, AdmissionSeat $admissionSeat): bool
    {
        return $this->allows($authUser, 'Restore:AdmissionSeat', $admissionSeat);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:AdmissionSeat');
    }

    public function forceDelete(AuthUser $authUser, AdmissionSeat $admissionSeat): bool
    {
        return $this->allows($authUser, 'ForceDelete:AdmissionSeat', $admissionSeat);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:AdmissionSeat');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdmissionSeat');
    }
}
