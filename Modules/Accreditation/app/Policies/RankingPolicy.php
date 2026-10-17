<?php

declare(strict_types=1);

namespace Modules\Accreditation\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Accreditation\Models\Ranking;
use Platform\Policies\ScopedPolicy;

/** Ranking: by permission and scope (Shield, Scope engine). */
class RankingPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Ranking');
    }

    public function view(AuthUser $authUser, Ranking $ranking): bool
    {
        return $this->allows($authUser, 'View:Ranking', $ranking);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Ranking');
    }

    public function update(AuthUser $authUser, Ranking $ranking): bool
    {
        return $this->allows($authUser, 'Update:Ranking', $ranking);
    }

    public function delete(AuthUser $authUser, Ranking $ranking): bool
    {
        return $this->allows($authUser, 'Delete:Ranking', $ranking);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Ranking');
    }

    public function restore(AuthUser $authUser, Ranking $ranking): bool
    {
        return $this->allows($authUser, 'Restore:Ranking', $ranking);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Ranking');
    }

    public function forceDelete(AuthUser $authUser, Ranking $ranking): bool
    {
        return $this->allows($authUser, 'ForceDelete:Ranking', $ranking);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Ranking');
    }
}
