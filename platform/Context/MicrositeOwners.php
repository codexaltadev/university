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
     * @return array<string, string|array<string, string>> "site:0" => "🌐 Main site", then entities grouped by kind
     */
    public static function options(?User $user, string $subject): array
    {
        if ($user === null) {
            return [];
        }

        $mayMain = $user->isSuperAdmin() || $user->can("ManageMain:{$subject}");
        $mayEntities = $user->isSuperAdmin() || $user->can("Manage:{$subject}");
        $microsites = collect(app(Registry::class)->all('micrositeEntity'))->map(fn (array $e): string => MorphMap::alias($e['model']))->all();

        $entities = $mayEntities ? collect(app(Targets::class)->options($user))
            ->map(fn (array $items): array => array_filter($items, fn (string $label, string $key): bool => in_array(Str::before($key, ':'), $microsites, true), ARRAY_FILTER_USE_BOTH))
            ->filter()->all() : [];

        return [...($mayMain ? [RelationEngine::SITE_KEY => '🌐 '.__('platform::relations.main_site')] : []), ...$entities];
    }

    /** @return list<string> */
    public static function keys(?User $user, string $subject): array
    {
        return collect(static::options($user, $subject))->flatMap(fn ($v, $k): array => is_array($v) ? array_keys($v) : [$k])->values()->all();
    }

    public static function label(string $ownerKey): string
    {
        return $ownerKey === RelationEngine::SITE_KEY ? __('platform::relations.main_site') : Str::after(app(Targets::class)->label($ownerKey), ': ');
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
