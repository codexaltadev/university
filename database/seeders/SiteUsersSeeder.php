<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Platform\Models\UserScope;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * One login per Faculty, Department and Office: sees and edits only that site's content.
 * Not part of DatabaseSeeder — run it on purpose: php artisan db:seed --class=SiteUsersSeeder
 *
 * Password and mail domain from .env (SITE_USERS_PASSWORD, SITE_USERS_EMAIL_DOMAIN); without a
 * password each user gets a random one and signs in through "Forgot password". Re-running
 * adds only what is missing and never changes an existing user or a role an admin has ticked.
 */
class SiteUsersSeeder extends Seeder
{
    /** morph alias => [mail prefix, role]; by alias: the app does not depend on a module */
    protected const SITES = [
        'faculty.faculty' => ['faculty', 'entity_editor'],
        'department.department' => ['dept', 'entity_editor'],
        'office.office' => ['office', 'office_editor'],
    ];

    /** Content of a site; "Activity" = CSR */
    protected const CONTENT = ['Notice', 'Post', 'Event', 'Album', 'Page', 'Faq', 'Document', 'Slide', 'Link', 'Testimonial', 'Achievement', 'Widget', 'Committee', 'Facility', 'Activity'];

    protected const CONTENT_ACTIONS = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'Reorder'];

    protected const MICROSITE = ['Manage:Menu', 'Manage:HeaderFooter', 'Manage:Homepage', 'Manage:Statistic'];

    protected const MEDIA = ['ViewAny:MediaItem', 'View:MediaItem', 'Create:MediaItem', 'Update:MediaItem', 'Delete:MediaItem'];

    /** Own profile only: a site user never creates or deletes a Faculty, Department or Office. */
    protected const PROFILE = [
        'entity_editor' => ['Faculty', 'Department', 'Program'],
        'office_editor' => ['Office'],
    ];

    public function run(): void
    {
        $this->fillEmptyRoles();

        $password = env('SITE_USERS_PASSWORD');
        $domain = env('SITE_USERS_EMAIL_DOMAIN', 'university.test');
        $made = $skipped = 0;

        foreach (self::SITES as $alias => [$prefix, $role]) {
            $model = Relation::getMorphedModel($alias);

            if (! $model || ! class_exists($model) || ! Schema::hasTable((new $model)->getTable())) {
                $this->command?->warn("Skipped {$alias}: module not installed.");

                continue;
            }

            foreach ($model::query()->get() as $site) {
                $this->userFor($site, $alias, $prefix, $role, $domain, $password) ? $made++ : $skipped++;
            }
        }

        $this->command?->info("Site users: {$made} created, {$skipped} already there.");

        if (! $password) {
            $this->command?->warn('No SITE_USERS_PASSWORD: random passwords, not shown. Use "Forgot password" or set one in System › Users.');
        }
    }

    protected function userFor(object $site, string $alias, string $prefix, string $role, string $domain, ?string $password): bool
    {
        // One per site, whatever its address became since
        $exists = User::withTrashed()->role($role)
            ->whereHas('scopes', fn ($q) => $q->where('scopeable_type', $alias)->where('scopeable_id', $site->getKey()))
            ->exists();

        $email = $prefix.'.'.($site->slug('en') ?? $site->getKey()).'@'.$domain;

        if ($exists || User::withTrashed()->where('email', $email)->exists()) {
            return false;
        }

        $user = User::query()->create([
            'name' => (string) $site->name,
            'email' => $email,
            'password' => $password ?: Str::password(24),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($role);

        UserScope::create(['user_id' => $user->id, 'scopeable_type' => $alias, 'scopeable_id' => $site->getKey()]);

        return true;
    }

    /** A role already ticked in the role editor stays as it is. */
    protected function fillEmptyRoles(): void
    {
        $content = collect(self::CONTENT)->crossJoin(self::CONTENT_ACTIONS)
            ->map(fn (array $pair): string => "{$pair[1]}:{$pair[0]}")->all();

        foreach (self::PROFILE as $name => $subjects) {
            $role = Role::findOrCreate($name, config('auth.defaults.guard'));

            if ($role->permissions()->exists()) {
                continue;
            }

            $profile = collect($subjects)->crossJoin(['ViewAny', 'View', 'Update'])
                ->map(fn (array $pair): string => "{$pair[1]}:{$pair[0]}")->all();

            // Only what exists: an uninstalled module has no permissions
            $role->givePermissionTo(Permission::query()
                ->whereIn('name', [...$content, ...self::MICROSITE, ...self::MEDIA, ...$profile])
                ->where('guard_name', $role->guard_name)
                ->get());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
