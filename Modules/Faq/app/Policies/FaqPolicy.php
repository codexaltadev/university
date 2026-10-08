<?php

declare(strict_types=1);

namespace Modules\Faq\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Faq\Models\Faq;
use Platform\Policies\ScopedPolicy;

class FaqPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Faq');
    }

    public function view(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'View:Faq', $faq);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Faq');
    }

    public function update(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'Update:Faq', $faq);
    }

    public function delete(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'Delete:Faq', $faq);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Faq');
    }

    public function restore(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'Restore:Faq', $faq);
    }

    public function forceDelete(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'ForceDelete:Faq', $faq);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Faq');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Faq');
    }

    public function replicate(AuthUser $authUser, Faq $faq): bool
    {
        return $this->allows($authUser, 'Replicate:Faq', $faq);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Faq');
    }
}
