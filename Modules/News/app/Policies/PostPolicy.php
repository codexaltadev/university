<?php

declare(strict_types=1);

namespace Modules\News\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\News\Models\Post;
use Platform\Policies\ScopedPolicy;

class PostPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Post');
    }

    public function view(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'View:Post', $post);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Post');
    }

    public function update(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'Update:Post', $post);
    }

    public function delete(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'Delete:Post', $post);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Post');
    }

    public function restore(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'Restore:Post', $post);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Post');
    }

    public function forceDelete(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'ForceDelete:Post', $post);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Post');
    }

    public function replicate(AuthUser $authUser, Post $post): bool
    {
        return $this->allows($authUser, 'Replicate:Post', $post);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Post');
    }
}
