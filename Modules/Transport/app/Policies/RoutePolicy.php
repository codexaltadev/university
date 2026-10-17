<?php

declare(strict_types=1);

namespace Modules\Transport\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Transport\Models\Route;
use Platform\Policies\ScopedPolicy;

/** Bus routes: by permission (Shield) — university-wide data, no entity scope. */
class RoutePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Route');
    }

    public function view(AuthUser $authUser, Route $route): bool
    {
        return $this->allows($authUser, 'View:Route', $route);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Route');
    }

    public function update(AuthUser $authUser, Route $route): bool
    {
        return $this->allows($authUser, 'Update:Route', $route);
    }

    public function delete(AuthUser $authUser, Route $route): bool
    {
        return $this->allows($authUser, 'Delete:Route', $route);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Route');
    }

    public function restore(AuthUser $authUser, Route $route): bool
    {
        return $this->allows($authUser, 'Restore:Route', $route);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Route');
    }

    public function forceDelete(AuthUser $authUser, Route $route): bool
    {
        return $this->allows($authUser, 'ForceDelete:Route', $route);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Route');
    }
}
