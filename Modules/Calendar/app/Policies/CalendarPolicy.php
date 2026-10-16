<?php

declare(strict_types=1);

namespace Modules\Calendar\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Calendar\Models\Calendar;
use Platform\Policies\ScopedPolicy;

/** Calendars: whoever has the permission (an Admin, or a role such as Registrar) manages them; their dates go with the calendar. */
class CalendarPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Calendar');
    }

    public function view(AuthUser $authUser, Calendar $calendar): bool
    {
        return $this->allows($authUser, 'View:Calendar', $calendar);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Calendar');
    }

    public function update(AuthUser $authUser, Calendar $calendar): bool
    {
        return $this->allows($authUser, 'Update:Calendar', $calendar);
    }

    public function delete(AuthUser $authUser, Calendar $calendar): bool
    {
        return $this->allows($authUser, 'Delete:Calendar', $calendar);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Calendar');
    }

    public function restore(AuthUser $authUser, Calendar $calendar): bool
    {
        return $this->allows($authUser, 'Restore:Calendar', $calendar);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Calendar');
    }

    public function forceDelete(AuthUser $authUser, Calendar $calendar): bool
    {
        return $this->allows($authUser, 'ForceDelete:Calendar', $calendar);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Calendar');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Calendar');
    }
}
