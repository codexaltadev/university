<?php

namespace Modules\User\Services;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\User\Notifications\UserInvitation;
use Platform\Models\UserScope;
use Platform\Registry\Registry;
use Platform\Scope\ScopeResolver;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * Scopes, invitations and the "effective permissions" view (FR-USER-07, FR-ROLE-06/09).
 */
class UserAccess
{
    public function __construct(protected Registry $registry, protected ScopeResolver $scope) {}

    /**
     * Entities a user can be scoped to, grouped by type label (registered with scopeable()).
     *
     * @return array<string, array<string, string>> label => ["type:id" => name]
     */
    public function scopeOptions(): array
    {
        $groups = [];

        foreach ($this->registry->all('scopeable') as $entry) {
            $model = $entry['model'];
            $type = MorphMap::alias($model);
            $label = Str::headline(Str::plural(class_basename($model)));

            $groups[$label] = $model::query()
                ->withoutGlobalScope(UserScopeFilter::class)
                ->get()
                ->mapWithKeys(fn ($record): array => ["{$type}:{$record->getKey()}" => (string) ($record->name ?? '#'.$record->getKey())])
                ->sort()
                ->all();
        }

        return array_filter($groups);
    }

    /** @return list<string> */
    public function scopeKeys(User $user): array
    {
        return $user->scopes()->get()->map->key()->all();
    }

    /** @param list<string> $keys */
    public function syncScopes(User $user, array $keys): void
    {
        DB::transaction(function () use ($user, $keys): void {
            UserScope::query()->where('user_id', $user->id)->delete();

            foreach (array_unique($keys) as $key) {
                [$type, $id] = explode(':', $key, 2);
                UserScope::create(['user_id' => $user->id, 'scopeable_type' => $type, 'scopeable_id' => (int) $id]);
            }
        });

        $this->scope->forget();   // UserScopeChanged
    }

    /** Email a link where the user sets their own password. */
    public function invite(User $user): void
    {
        $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);

        $user->notify(new UserInvitation(Filament::getResetPasswordUrl($token, $user)));
        $user->forceFill(['invited_at' => now()])->saveQuietly();
    }

    /**
     * What the user can really do (FR-ROLE-09): permissions from all roles, and where.
     *
     * @return array{super_admin: bool, permissions: Collection<int, string>, scope: list<string>|null}
     */
    public function effective(User $user): array
    {
        return [
            'super_admin' => $user->isSuperAdmin(),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'scope' => $this->scope->keysFor($user),
        ];
    }
}
