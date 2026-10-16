<?php

declare(strict_types=1);

namespace Modules\Announcement\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Announcement\Models\Widget;
use Platform\Policies\ScopedPolicy;

/** Widgets: by permission and scope — an entity user manages the widgets shown in their entities. */
class WidgetPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Widget');
    }

    public function view(AuthUser $authUser, Widget $widget): bool
    {
        return $this->allows($authUser, 'View:Widget', $widget);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Widget');
    }

    public function update(AuthUser $authUser, Widget $widget): bool
    {
        return $this->allows($authUser, 'Update:Widget', $widget);
    }

    public function delete(AuthUser $authUser, Widget $widget): bool
    {
        return $this->allows($authUser, 'Delete:Widget', $widget);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Widget');
    }

    public function restore(AuthUser $authUser, Widget $widget): bool
    {
        return $this->allows($authUser, 'Restore:Widget', $widget);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Widget');
    }

    public function forceDelete(AuthUser $authUser, Widget $widget): bool
    {
        return $this->allows($authUser, 'ForceDelete:Widget', $widget);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Widget');
    }
}
