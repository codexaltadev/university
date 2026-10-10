<?php

namespace Platform\Context;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * "Working in" (UI/UX Part A §4.1): the microsite the user is working on — the main site or
 * one Faculty / Department / Program … of their scope — kept in the session. The microsite
 * screens (Menus, Header & Footer, Homepage, Statistics, new Pages) open on it, and opening
 * one of them on an owner makes that the current one.
 */
class WorkingIn
{
    public const SESSION_KEY = 'uwms.working_in';

    public static function current(): ?string
    {
        $key = session(self::SESSION_KEY);

        return is_string($key) ? $key : null;
    }

    public static function set(?string $ownerKey): void
    {
        session([self::SESSION_KEY => $ownerKey]);
    }

    /** The current owner when it is one of $allowed, else the first allowed. */
    public static function pick(array $allowed): ?string
    {
        $current = static::current();

        return in_array($current, $allowed, true) ? $current : ($allowed[0] ?? null);
    }

    public static function keyOf(Model $entity): string
    {
        return $entity->getMorphClass().':'.$entity->getKey();
    }

    /**
     * Owners to switch between: the main site for unscoped users, then the microsite
     * entities of the user's scope, grouped by kind.
     *
     * @return array<string, string|array<string, string>>
     */
    public static function options(?User $user = null): array
    {
        $user ??= Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        $microsites = collect(app(Registry::class)->all('micrositeEntity'))->map(fn (array $e): string => MorphMap::alias($e['model']))->all();
        $entities = collect(app(Targets::class)->options($user))
            ->map(fn (array $items): array => array_filter($items, fn (string $label, string $key): bool => in_array(Str::before($key, ':'), $microsites, true), ARRAY_FILTER_USE_BOTH))
            ->filter()->all();
        $unscoped = $user->isSuperAdmin() || ! $user->scopes()->exists();

        return [...($unscoped ? [RelationEngine::SITE_KEY => '🌐 '.__('platform::relations.main_site')] : []), ...$entities];
    }

    /** @return list<string> */
    public static function keys(?User $user = null): array
    {
        return collect(static::options($user))->flatMap(fn ($v, $k): array => is_array($v) ? array_keys($v) : [$k])->values()->all();
    }

    /** @return list<class-string<MicrositeTool>> the registered microsite screens */
    public static function tools(): array
    {
        return collect(app(Registry::class)->all('micrositeTool'))->pluck('class')->filter(fn (string $c): bool => class_exists($c))->values()->all();
    }

    /** The entity behind an owner key, or null for the main site. */
    public static function entity(string $ownerKey): ?Model
    {
        if ($ownerKey === RelationEngine::SITE_KEY || ! str_contains($ownerKey, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ownerKey, 2);
        $model = Relation::getMorphedModel($type);

        return $model ? UserScopeFilter::withoutScope(fn () => $model::query()->find($id)) : null;
    }

    /**
     * "My Microsite": the editor of the scoped user's own microsite entity (their first
     * one); null for unscoped users.
     */
    public static function myMicrositeUrl(?User $user = null): ?string
    {
        $user ??= Auth::user();
        if (! $user instanceof User || $user->isSuperAdmin() || ! $user->scopes()->exists()) {
            return null;
        }

        $entityKeys = array_values(array_filter(static::keys($user), fn (string $k): bool => $k !== RelationEngine::SITE_KEY));
        $key = in_array(static::current(), $entityKeys, true) ? static::current() : ($entityKeys[0] ?? null);
        $entity = $key ? static::entity($key) : null;
        $resource = $entity ? Filament::getModelResource($entity) : null;

        if ($entity === null || $resource === null) {
            return null;
        }

        /** @var class-string<resource> $resource */
        foreach (['edit', 'view'] as $page) {
            if ($resource::hasPage($page) && $resource::getPages()[$page]->getPage()::canAccess(['record' => $entity])) {
                return $resource::getUrl($page, ['record' => $entity]);
            }
        }

        return null;
    }
}
