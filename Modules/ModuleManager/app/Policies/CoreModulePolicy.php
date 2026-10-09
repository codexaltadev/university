<?php

declare(strict_types=1);

namespace Modules\ModuleManager\Policies;

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Models\CoreModule;
use Platform\Policies\ScopedPolicy;

/**
 * Module Manager is for Super Admins only (SRS C-01 actors). Not offered in the
 * role editor: a role permission here would only suggest access nobody gets.
 */
class CoreModulePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->isSuperAdmin($authUser);
    }

    public function view(AuthUser $authUser, CoreModule $module): bool
    {
        return $this->isSuperAdmin($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->isSuperAdmin($authUser);   // install from ZIP
    }

    public function update(AuthUser $authUser, CoreModule $module): bool
    {
        return $this->isSuperAdmin($authUser);   // activate / deactivate
    }

    public function delete(AuthUser $authUser, CoreModule $module): bool
    {
        return $this->isSuperAdmin($authUser) && $module->type !== 'core';   // uninstall
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    protected function isSuperAdmin(AuthUser $user): bool
    {
        return $user instanceof User && $user->isSuperAdmin();
    }
}
