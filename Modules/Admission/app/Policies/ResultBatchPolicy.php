<?php

declare(strict_types=1);

namespace Modules\Admission\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Admission\Models\ResultBatch;
use Platform\Policies\ScopedPolicy;

/** Admission data has no scope: whoever has the permission (by default the Admission Office) sees it all. */
class ResultBatchPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:ResultBatch');
    }

    public function view(AuthUser $authUser, ResultBatch $resultBatch): bool
    {
        return $this->allows($authUser, 'View:ResultBatch', $resultBatch);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:ResultBatch');
    }

    public function update(AuthUser $authUser, ResultBatch $resultBatch): bool
    {
        return $this->allows($authUser, 'Update:ResultBatch', $resultBatch);
    }

    public function delete(AuthUser $authUser, ResultBatch $resultBatch): bool
    {
        return $this->allows($authUser, 'Delete:ResultBatch', $resultBatch);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:ResultBatch');
    }

    public function restore(AuthUser $authUser, ResultBatch $resultBatch): bool
    {
        return $this->allows($authUser, 'Restore:ResultBatch', $resultBatch);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:ResultBatch');
    }

    public function forceDelete(AuthUser $authUser, ResultBatch $resultBatch): bool
    {
        return $this->allows($authUser, 'ForceDelete:ResultBatch', $resultBatch);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:ResultBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:ResultBatch');
    }
}
