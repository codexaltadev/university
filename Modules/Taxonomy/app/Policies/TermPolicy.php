<?php

declare(strict_types=1);

namespace Modules\Taxonomy\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Policies\ScopedPolicy;
use Platform\Taxonomy\Term;

/** One permission for all of taxonomy: Manage:Taxonomy (SRS C-16 "taxonomy.manage"). */
class TermPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function view(AuthUser $authUser, Term $term): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function update(AuthUser $authUser, Term $term): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function delete(AuthUser $authUser, Term $term): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Term $term): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function forceDelete(AuthUser $authUser, Term $term): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }
}
