<?php

namespace Modules\Menu\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeSetting;
use Platform\Context\SiteContext;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * What the Menus screen does (FR-MENU-01/02/06/08/09): find an owner's own menu,
 * create one (empty or copied from the menu it uses now), load and save the item
 * tree, delete (back to the fallback), choose the menu source of a microsite.
 */
class MenuEditor
{
    /** @return array<string, string> location => label (FR-MENU-01; themes add more later) */
    public static function locations(): array
    {
        return collect(config('menu.locations', []))
            ->mapWithKeys(fn (string $key): array => [$key => __("menu::menu.locations.{$key}")])
            ->all();
    }

    /**
     * Owners this user may edit menus of (BRL-MENU-02): the main site with
     * ManageMain:Menu, microsite entities of their scope with Manage:Menu.
     *
     * @return array<string, string|array<string, string>>
     */
    public static function ownerOptions(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $mayMain = $user->isSuperAdmin() || $user->can('ManageMain:Menu');
        $mayEntities = $user->isSuperAdmin() || $user->can('Manage:Menu');
        $microsites = collect(app(Registry::class)->all('micrositeEntity'))->map(fn (array $e): string => MorphMap::alias($e['model']))->all();

        $entities = $mayEntities ? collect(app(Targets::class)->options($user))
            ->map(fn (array $items): array => array_filter($items, fn (string $label, string $key): bool => in_array(Str::before($key, ':'), $microsites, true), ARRAY_FILTER_USE_BOTH))
            ->filter()->all() : [];

        return [...($mayMain ? [RelationEngine::SITE_KEY => '🌐 '.__('platform::relations.main_site')] : []), ...$entities];
    }

    /** @return list<string> */
    public static function ownerKeys(?User $user): array
    {
        return collect(static::ownerOptions($user))->flatMap(fn ($v, $k): array => is_array($v) ? array_keys($v) : [$k])->values()->all();
    }

    public function own(string $ownerKey, string $location): ?Menu
    {
        return Menu::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($ownerKey)->where('location', $location)->first();
    }

    /** The menu used at this owner and location now, and whose it is (FallbackIndicator). */
    public function current(string $ownerKey, string $location): array
    {
        $context = $this->context($ownerKey);
        $resolver = app(FallbackResolver::class);

        return ['menu' => $resolver->resolve('menu', $location, $context), 'from' => $resolver->source('menu', $location, $context)];
    }

    /** FR-MENU-09: start an own menu, empty or as a copy of another. */
    public function create(string $ownerKey, string $location, ?Menu $copyOf = null): Menu
    {
        return DB::transaction(function () use ($ownerKey, $location, $copyOf): Menu {
            $menu = (new Menu(['name' => $this->ownerLabel($ownerKey).' — '.(static::locations()[$location] ?? $location), 'location' => $location]))
                ->setOwnerKey($ownerKey);
            $menu->save();

            if ($copyOf) {
                $this->save($menu, $this->tree($copyOf, withIds: false));
            }

            MenuTree::flush();

            return $menu;
        });
    }

    /** Own menu removed: the owner uses the fallback again (AC-MENU-02). */
    public function delete(Menu $menu): void
    {
        $menu->delete();
        MenuTree::flush();
    }

    /** FR-MENU-08: own / inherit (parent first, default) / main. */
    public function setMode(string $ownerKey, string $mode): void
    {
        [$type, $id] = explode(':', $ownerKey, 2);
        $setting = MicrositeSetting::query()->firstOrNew(['owner_type' => $type, 'owner_id' => (int) $id]);
        $setting->fallback_modes = [...(array) $setting->fallback_modes, 'menu' => $mode];
        $setting->save();

        MenuTree::flush();
    }

    public function mode(string $ownerKey): string
    {
        [$type, $id] = explode(':', $ownerKey, 2);

        return MicrositeSetting::query()->where('owner_type', $type)->where('owner_id', (int) $id)->first()?->modeFor('menu') ?? 'inherit';
    }

    /**
     * Form state of a menu: nested items.
     *
     * @return list<array<string, mixed>>
     */
    public function tree(Menu $menu, bool $withIds = true): array
    {
        $items = $menu->items()->get();

        $build = function (?int $parent) use (&$build, $items, $withIds): array {
            return $items->where('parent_id', $parent)->map(fn (MenuItem $item): array => [
                ...($withIds ? ['id' => $item->id] : []),
                'type' => $item->type,
                'record_type' => $item->type === 'record' ? $item->linkable_type : null,
                'record_id' => $item->type === 'record' ? $item->linkable_id : null,
                'term_id' => $item->type === 'taxonomy' ? $item->linkable_id : null,
                'file_id' => $item->type === 'file' ? $item->linkable_id : null,
                'route_key' => $item->route_key,
                'url' => $item->url,
                'title' => $item->getTranslations('title'),
                'icon' => $item->icon,
                'open_in_new_tab' => $item->open_in_new_tab,
                'css_class' => $item->css_class,
                'is_active' => $item->is_active,
                'children' => $build($item->id),
            ])->values()->all();
        };

        return $build(null);
    }

    /**
     * Save the tree: existing items updated, new ones created, missing ones deleted.
     *
     * @param  array<int|string, array<string, mixed>>  $tree
     */
    public function save(Menu $menu, array $tree): void
    {
        DB::transaction(function () use ($menu, $tree): void {
            $kept = [];
            $this->saveLevel($menu, array_values($tree), null, 1, $kept);

            $menu->items()->whereNotIn('id', $kept)->get()->each->forceDelete();
        });

        MenuTree::flush();
    }

    /** @param list<array<string, mixed>> $items */
    protected function saveLevel(Menu $menu, array $items, ?int $parentId, int $depth, array &$kept): void
    {
        foreach ($items as $position => $data) {
            $type = $data['type'] ?? 'url';
            [$linkType, $linkId] = match ($type) {
                'record' => [$data['record_type'] ?? null, $data['record_id'] ?? null],
                'taxonomy' => ['core.term', $data['term_id'] ?? null],
                'file' => ['core.media-item', $data['file_id'] ?? null],
                default => [null, null],
            };

            $item = (isset($data['id']) ? $menu->items()->find($data['id']) : null) ?? new MenuItem(['menu_id' => $menu->id]);
            $item->fill([
                'parent_id' => $parentId,
                'type' => $type,
                'linkable_type' => $linkType,
                'linkable_id' => $linkId ? (int) $linkId : null,
                'route_key' => $type === 'module_page' ? ($data['route_key'] ?? null) : null,
                'url' => $type === 'url' ? ($data['url'] ?? null) : null,
                'title' => array_filter((array) ($data['title'] ?? []), fn ($t): bool => filled($t)) ?: null,
                'icon' => $data['icon'] ?? null,
                'open_in_new_tab' => (bool) ($data['open_in_new_tab'] ?? false),
                'css_class' => $data['css_class'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'sort_order' => $position + 1,
            ]);
            $item->menu_id = $menu->id;
            $item->save();
            $kept[] = $item->id;

            if ($depth < MenuItem::MAX_DEPTH) {
                $this->saveLevel($menu, array_values((array) ($data['children'] ?? [])), $item->id, $depth + 1, $kept);
            }
        }
    }

    public function ownerLabel(string $ownerKey): string
    {
        return $ownerKey === RelationEngine::SITE_KEY ? __('platform::relations.main_site') : Str::after(app(Targets::class)->label($ownerKey), ': ');
    }

    public function context(string $ownerKey): SiteContext
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
