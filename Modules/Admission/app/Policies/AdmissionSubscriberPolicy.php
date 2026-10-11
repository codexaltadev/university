<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\AdmissionSubscriber;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class AdmissionSubscriberPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdmissionSubscriber');
    }

    public function view(AuthUser $authUser, AdmissionSubscriber $admissionSubscriber): bool
    {
        return $this->allows($authUser, 'View:AdmissionSubscriber', $admissionSubscriber);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdmissionSubscriber');
    }

    public function update(AuthUser $authUser, AdmissionSubscriber $admissionSubscriber): bool
    {
        return $this->allows($authUser, 'Update:AdmissionSubscriber', $admissionSubscriber);
    }

    public function delete(AuthUser $authUser, AdmissionSubscriber $admissionSubscriber): bool
    {
        return $this->allows($authUser, 'Delete:AdmissionSubscriber', $admissionSubscriber);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdmissionSubscriber');
    }

    public function restore(AuthUser $authUser, AdmissionSubscriber $admissionSubscriber): bool
    {
        return $this->allows($authUser, 'Restore:AdmissionSubscriber', $admissionSubscriber);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:AdmissionSubscriber');
    }

    public function forceDelete(AuthUser $authUser, AdmissionSubscriber $admissionSubscriber): bool
    {
        return $this->allows($authUser, 'ForceDelete:AdmissionSubscriber', $admissionSubscriber);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:AdmissionSubscriber');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdmissionSubscriber');
    }
}
