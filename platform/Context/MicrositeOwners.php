<?php

namespace Platform\Context;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * Who may edit what a microsite owns (menus, header / footer, homepage, statistics —
 * BRL-MENU-02 and the same rule elsewhere): the main site with "ManageMain:{Subject}",
 * microsite entities of the user's scope with "Manage:{Subject}".
 */
class MicrositeOwners
{
    /**
     * @param  bool  $withKinds  also "every Department" … owners ({alias}:0): what all microsites
     *                           of a kind share unless one has its own (main site permission)
     * @return array<string, string|array<string, string>> "site:0" => "🌐 Main site", then entities grouped by kind
     */
    public static function options(?User $user, string $subject, bool $withKinds = false): array
    {
        if ($user === null) {
            return [];
        }

        $mayMain = $user->isSuperAdmin() || $user->can("ManageMain:{$subject}");
        $mayEntities = $user->isSuperAdmin() || $user->can("Manage:{$subject}");
        $microsites = static::kinds();

        $entities = $mayEntities ? collect(app(Targets::class)->options($user))
            ->map(fn (array $items): array => array_filter($items, fn (string $label, string $key): bool => in_array(Str::before($key, ':'), $microsites, true), ARRAY_FILTER_USE_BOTH))
            ->filter()->all() : [];

        $kinds = $withKinds && $mayMain
            ? [__('platform::relations.every_kind_group') => collect($microsites)->mapWithKeys(fn (string $alias): array => [static::kindKey($alias) => '🧩 '.static::label(static::kindKey($alias))])->all()]
            : [];

        return [...($mayMain ? [RelationEngine::SITE_KEY => '🌐 '.__('platform::relations.main_site')] : []), ...$kinds, ...$entities];
    }

    /** @return list<string> */
    public static function keys(?User $user, string $subject, bool $withKinds = false): array
    {
        return collect(static::options($user, $subject, $withKinds))->flatMap(fn ($v, $k): array => is_array($v) ? array_keys($v) : [$k])->values()->all();
    }

    public static function label(string $ownerKey): string
    {
        if (static::isKindKey($ownerKey)) {
            $model = Relation::getMorphedModel(Str::before($ownerKey, ':'));

            return __('platform::relations.every_kind', ['kind' => Str::plural(Str::headline(class_basename((string) $model)))]);
        }

        return $ownerKey === RelationEngine::SITE_KEY ? __('platform::relations.main_site') : Str::after(app(Targets::class)->label($ownerKey), ': ');
    }

    /** @return list<string> morph aliases of the microsite kinds (faculty.faculty, department.department …) */
    public static function kinds(): array
    {
        return collect(app(Registry::class)->all('micrositeEntity'))->map(fn (array $e): string => MorphMap::alias($e['model']))->values()->all();
    }

    /** "department.department:0" — everything a kind of microsite shares */
    public static function kindKey(string $alias): string
    {
        return "{$alias}:0";
    }

    public static function isKindKey(string $ownerKey): bool
    {
        return str_ends_with($ownerKey, ':0') && $ownerKey !== RelationEngine::SITE_KEY && in_array(Str::before($ownerKey, ':'), static::kinds(), true);
    }

    /** The kind key of a microsite entity's key ("department.department:12" → "department.department:0"), null for the main site */
    public static function kindOf(string $ownerKey): ?string
    {
        return $ownerKey === RelationEngine::SITE_KEY || static::isKindKey($ownerKey) || ! in_array(Str::before($ownerKey, ':'), static::kinds(), true)
            ? null
            : static::kindKey(Str::before($ownerKey, ':'));
    }

    public static function context(string $ownerKey): SiteContext
    {
        if ($ownerKey === RelationEngine::SITE_KEY) {
            return SiteContext::site();
        }

        [$type, $id] = explode(':', $ownerKey, 2);
        $model = Relation::getMorphedModel($type);
        $entity = $model ? $model::query()->withoutGlobalScope(UserScopeFilter::class)->find($id) : null;

        return $entity instanceof Model ? SiteContext::for($entity) : SiteContext::site();
    }
}
