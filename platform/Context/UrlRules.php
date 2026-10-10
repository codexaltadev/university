<?php

namespace Platform\Context;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Concerns\HasSlug;
use Platform\Relations\RelationEngine;
use Platform\Scope\UserScopeFilter;

/**
 * Website address clashes (SRS FR-GEN-35), checked when a slug is saved:
 *
 *  - a top entity ("fsit" opens "/fsit/cse") may not take a reserved first segment or a main
 *    site page's address, and a main site page may not take a top entity's slug;
 *  - inside a microsite, a child ("/fsit/cse/bsc-in-cse") may not take a section path or the
 *    address of a microsite page of its parent's kind, and such a page not a child's slug.
 *
 * Pages live in a module the kernel does not know: it tells how to look them up with
 * UrlRules::pagesUsing(fn (string $ownerKey|null, string $ownerType, string $slug): bool).
 */
class UrlRules
{
    /** @var Closure(?string, ?string, string): bool|null top-level page with this slug (by owner key, or by owner type for any owner of it) */
    protected static ?Closure $pages = null;

    public static function pagesUsing(?Closure $lookup): void
    {
        static::$pages = $lookup;
    }

    /** @return list<string> first segments of the main site */
    public static function reservedRoot(): array
    {
        return array_values(array_unique([
            ...config('platform.reserved_paths', []),
            ...config('platform.locales', []),
            ...array_keys(app(MicrositeResolver::class)->prefixes()),
        ]));
    }

    /** @return list<string> section paths inside a microsite */
    public static function reservedInside(): array
    {
        $entities = app(MicrositeResolver::class)->entities();

        return array_values(array_unique([
            ...config('platform.reserved_microsite_paths', []),
            ...array_filter(array_column($entities, 'childSegment')),
            ...collect($entities)->filter(fn (array $e): bool => $e['parent'] !== null)->pluck('routePrefix')->all(),
        ]));
    }

    /**
     * Why a microsite entity may not have this slug, or null.
     *
     * @param  class-string<Model>  $model
     */
    public static function entityClash(string $model, string $slug, ?Model $record = null): ?string
    {
        $slug = Str::slug($slug);
        $resolver = app(MicrositeResolver::class);
        $entry = $resolver->entities()[$model] ?? null;
        if ($entry === null || $slug === '') {
            return null;
        }

        if ($entry['parent'] === null && $resolver->childrenOf($model) !== []) {
            if (in_array($slug, static::reservedRoot(), true)) {
                return __('platform::relations.address_reserved', ['address' => $slug]);
            }
            if (static::$pages && (static::$pages)(RelationEngine::SITE_KEY, null, $slug)) {
                return __('platform::relations.address_taken_page', ['address' => $slug]);
            }
        }

        if ($entry['parent'] !== null) {
            if (in_array($slug, static::reservedInside(), true)) {
                return __('platform::relations.address_reserved', ['address' => $slug]);
            }
            if (static::$pages && (static::$pages)(null, (new $entry['parent'])->getMorphClass(), $slug)) {
                return __('platform::relations.address_taken_page', ['address' => $slug]);
            }
        }

        return null;
    }

    /** Why a top-level page of this owner ("site:0", "department.department:12") may not have this slug, or null. */
    public static function pageClash(string $ownerKey, string $slug): ?string
    {
        $slug = Str::slug($slug);
        $resolver = app(MicrositeResolver::class);
        if ($slug === '') {
            return null;
        }

        if ($ownerKey === RelationEngine::SITE_KEY) {
            if (in_array($slug, array_diff(static::reservedRoot(), ['contact']), true)) {   // a "Contact" page is fine without the Contact module
                return __('platform::relations.address_reserved', ['address' => $slug]);
            }
            foreach ($resolver->roots() as $root) {
                if (static::slugTaken($root, $slug)) {
                    return __('platform::relations.address_taken_entity', ['address' => $slug]);
                }
            }

            return null;
        }

        $entity = WorkingIn::entity($ownerKey);
        if ($entity === null) {
            return null;
        }

        foreach ($resolver->childrenOf($entity::class) as $child) {
            if (static::slugTaken($child, $slug)) {
                return __('platform::relations.address_taken_entity', ['address' => $slug]);
            }
        }

        return $resolver->childrenOf($entity::class) !== [] && in_array($slug, static::reservedInside(), true)
            ? __('platform::relations.address_reserved', ['address' => $slug])
            : null;
    }

    /** @param class-string<Model> $model */
    protected static function slugTaken(string $model, string $slug): bool
    {
        if (! in_array(HasSlug::class, class_uses_recursive($model), true)) {
            return false;
        }

        return UserScopeFilter::withoutScope(fn () => $model::findBySlug($slug)) !== null;
    }
}
