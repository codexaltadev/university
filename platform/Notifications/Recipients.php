<?php

namespace Platform\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Platform\Scope\ScopeResolver;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Recipients by role, permission and scope (FR-NOTI-07): CSE's inquiry reaches the
 * users who may see inquiries and whose scope includes CSE — and Super Admins.
 */
final class Recipients
{
    /** @return Collection<int, User> active users with the permission who may see $record */
    public static function permitted(string $permission, ?Model $record = null, bool $superAdmins = true): Collection
    {
        try {
            $users = User::query()->where('is_active', true)->permission($permission)->get();
        } catch (PermissionDoesNotExist) {
            $users = new Collection;
        }

        if ($record !== null) {
            $users = $users->filter(fn (User $user): bool => app(ScopeResolver::class)->allows($user, $record))->values();
        }

        return $superAdmins ? $users->merge(self::superAdmins())->unique('id')->values() : $users;
    }

    /** @return Collection<int, User> */
    public static function superAdmins(): Collection
    {
        try {
            return User::query()->where('is_active', true)->role(config('filament-shield.super_admin.name'))->get();
        } catch (RoleDoesNotExist) {
            return new Collection;   // fresh install before roles are seeded
        }
    }

    /**
     * Users responsible for an entity: active users whose scope includes it and who
     * hold the permission (Content Update Reminder, FR-NOTI-12). No Super Admins.
     *
     * @return Collection<int, User>
     */
    public static function responsibleFor(Model $entity, string $permission): Collection
    {
        return self::permitted($permission, $entity, superAdmins: false)
            ->filter(fn (User $user): bool => $user->scopes()->exists())   // unscoped admins get the weekly summary instead
            ->values();
    }
}
