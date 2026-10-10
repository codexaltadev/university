<?php

declare(strict_types=1);

namespace Modules\Waiver\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Waiver\Models\Board;
use Platform\Policies\ScopedPolicy;

class BoardPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Board');
    }

    public function view(AuthUser $authUser, Board $board): bool
    {
        return $this->allows($authUser, 'View:Board', $board);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Board');
    }

    public function update(AuthUser $authUser, Board $board): bool
    {
        return $this->allows($authUser, 'Update:Board', $board);
    }

    public function delete(AuthUser $authUser, Board $board): bool
    {
        return $this->allows($authUser, 'Delete:Board', $board);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Board');
    }

    public function restore(AuthUser $authUser, Board $board): bool
    {
        return $this->allows($authUser, 'Restore:Board', $board);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Board');
    }

    public function forceDelete(AuthUser $authUser, Board $board): bool
    {
        return $this->allows($authUser, 'ForceDelete:Board', $board);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Board');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Board');
    }
}
