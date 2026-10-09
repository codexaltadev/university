<?php

declare(strict_types=1);

namespace Modules\Fee\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Fee\Models\WaiverRule;
use Platform\Policies\ScopedPolicy;

class WaiverRulePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:WaiverRule');
    }

    public function view(AuthUser $authUser, WaiverRule $waiverRule): bool
    {
        return $this->allows($authUser, 'View:WaiverRule', $waiverRule);
    }

    /** Create a record. */
    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:WaiverRule');
    }

    public function update(AuthUser $authUser, WaiverRule $waiverRule): bool
    {
        return $this->allows($authUser, 'Update:WaiverRule', $waiverRule);
    }

    public function delete(AuthUser $authUser, WaiverRule $waiverRule): bool
    {
        return $this->allows($authUser, 'Delete:WaiverRule', $waiverRule);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:WaiverRule');
    }

    public function restore(AuthUser $authUser, WaiverRule $waiverRule): bool
    {
        return $this->allows($authUser, 'Restore:WaiverRule', $waiverRule);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:WaiverRule');
    }

    public function forceDelete(AuthUser $authUser, WaiverRule $waiverRule): bool
    {
        return $this->allows($authUser, 'ForceDelete:WaiverRule', $waiverRule);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:WaiverRule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:WaiverRule');
    }
}
