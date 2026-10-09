<?php

namespace Modules\Settings\Support;

use App\Models\User;

/**
 * SRS C-05 permissions (manifest custom_permissions):
 * View:Settings — open the page; Update:Settings — save;
 * Advanced:Settings — mail and maintenance tabs. Custom code: Super Admin only.
 */
class SettingsAccess
{
    public static function view(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || $user->can('View:Settings') || $user->can('Update:Settings'));
    }

    public static function update(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || $user->can('Update:Settings'));
    }

    public static function advanced(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || $user->can('Advanced:Settings'));
    }
}
