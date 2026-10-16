<?php

declare(strict_types=1);

namespace Modules\Testimonials\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Testimonials\Models\Testimonial;
use Platform\Policies\ScopedPolicy;

/** Testimonials: by permission and scope — an entity user manages the testimonials shown in their entities. */
class TestimonialPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Testimonial');
    }

    public function view(AuthUser $authUser, Testimonial $testimonial): bool
    {
        return $this->allows($authUser, 'View:Testimonial', $testimonial);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Testimonial');
    }

    public function update(AuthUser $authUser, Testimonial $testimonial): bool
    {
        return $this->allows($authUser, 'Update:Testimonial', $testimonial);
    }

    public function delete(AuthUser $authUser, Testimonial $testimonial): bool
    {
        return $this->allows($authUser, 'Delete:Testimonial', $testimonial);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Testimonial');
    }

    public function restore(AuthUser $authUser, Testimonial $testimonial): bool
    {
        return $this->allows($authUser, 'Restore:Testimonial', $testimonial);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Testimonial');
    }

    public function forceDelete(AuthUser $authUser, Testimonial $testimonial): bool
    {
        return $this->allows($authUser, 'ForceDelete:Testimonial', $testimonial);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Testimonial');
    }
}
