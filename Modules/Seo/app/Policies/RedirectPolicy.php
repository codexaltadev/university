<?php

declare(strict_types=1);

namespace Modules\Seo\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Seo\Models\Redirect;
use Platform\Policies\ScopedPolicy;

class RedirectPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Redirect');
    }

    public function view(AuthUser $authUser, Redirect $redirect): bool
    {
        return $this->allows($authUser, 'View:Redirect', $redirect);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Redirect');
    }

    public function update(AuthUser $authUser, Redirect $redirect): bool
    {
        return $this->allows($authUser, 'Update:Redirect', $redirect);
    }

    public function delete(AuthUser $authUser, Redirect $redirect): bool
    {
        return $this->allows($authUser, 'Delete:Redirect', $redirect);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Redirect');
    }

    public function restore(AuthUser $authUser, Redirect $redirect): bool
    {
        return $this->allows($authUser, 'Restore:Redirect', $redirect);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Redirect');
    }

    public function forceDelete(AuthUser $authUser, Redirect $redirect): bool
    {
        return $this->allows($authUser, 'ForceDelete:Redirect', $redirect);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Redirect');
    }
}
