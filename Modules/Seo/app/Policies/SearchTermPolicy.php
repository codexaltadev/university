<?php

declare(strict_types=1);

namespace Modules\Seo\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Seo\Models\SearchTerm;
use Platform\Policies\ScopedPolicy;

/** The search terms are read and cleared; rows are written by the website only. */
class SearchTermPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:SearchTerm');
    }

    public function view(AuthUser $authUser, SearchTerm $term): bool
    {
        return $this->allows($authUser, 'ViewAny:SearchTerm');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, SearchTerm $term): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, SearchTerm $term): bool
    {
        return $this->allows($authUser, 'Delete:SearchTerm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:SearchTerm');
    }
}
