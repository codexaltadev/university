<?php

declare(strict_types=1);

namespace Modules\Administration\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Administration\Models\AdministrationCategory;
use Platform\Policies\ScopedPolicy;

/** Staff categories: by permission; one with members is not removed. */
class AdministrationCategoryPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:AdministrationCategory');
    }

    public function view(AuthUser $authUser, AdministrationCategory $category): bool
    {
        return $this->allows($authUser, 'View:AdministrationCategory', $category);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:AdministrationCategory');
    }

    public function update(AuthUser $authUser, AdministrationCategory $category): bool
    {
        return $this->allows($authUser, 'Update:AdministrationCategory', $category);
    }

    public function delete(AuthUser $authUser, AdministrationCategory $category): bool
    {
        return $this->allows($authUser, 'Delete:AdministrationCategory', $category) && ! $category->members()->withTrashed()->exists();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:AdministrationCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:AdministrationCategory');
    }
}
