<?php

declare(strict_types=1);

namespace Modules\Seo\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Seo\Models\NotFoundLog;
use Platform\Policies\ScopedPolicy;

/** The 404 log is read and cleared; rows are written by the website only. */
class NotFoundLogPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:NotFoundLog');
    }

    public function view(AuthUser $authUser, NotFoundLog $log): bool
    {
        return $this->allows($authUser, 'ViewAny:NotFoundLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, NotFoundLog $log): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, NotFoundLog $log): bool
    {
        return $this->allows($authUser, 'Delete:NotFoundLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:NotFoundLog');
    }
}
