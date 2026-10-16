<?php

declare(strict_types=1);

namespace Modules\Slider\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Slider\Models\Slide;
use Platform\Policies\ScopedPolicy;

/** Slides: by permission and scope — an entity user manages the slides shown in their entities. */
class SlidePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Slide');
    }

    public function view(AuthUser $authUser, Slide $slide): bool
    {
        return $this->allows($authUser, 'View:Slide', $slide);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Slide');
    }

    public function update(AuthUser $authUser, Slide $slide): bool
    {
        return $this->allows($authUser, 'Update:Slide', $slide);
    }

    public function delete(AuthUser $authUser, Slide $slide): bool
    {
        return $this->allows($authUser, 'Delete:Slide', $slide);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Slide');
    }

    public function restore(AuthUser $authUser, Slide $slide): bool
    {
        return $this->allows($authUser, 'Restore:Slide', $slide);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Slide');
    }

    public function forceDelete(AuthUser $authUser, Slide $slide): bool
    {
        return $this->allows($authUser, 'ForceDelete:Slide', $slide);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Slide');
    }
}
