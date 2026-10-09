<?php

namespace Platform\Context;

use Closure;
use InvalidArgumentException;
use Platform\Relations\RelationEngine;

/**
 * One resolver for everything a microsite may own or inherit (SAD Part B §6.2):
 * menu, header, footer, homepage, stats, template, logo, banner.
 *
 *   mode  own     → [entity]
 *         inherit → [entity, ...ancestors, site]   (default, BRL-MENU-01)
 *         main    → [entity, site]
 *
 * Core modules register a store per resource; a store returns the active item an
 * owner has at a location, or null. Nothing found → the theme default.
 *
 *   $resolver->store('menu', fn (string $type, int $id, ?string $location) => Menu::...->first());
 *   $resolver->resolve('menu', 'primary', $context);
 */
class FallbackResolver
{
    /** @var array<string, Closure(string, int, ?string): mixed> */
    protected array $stores = [];

    /** @var array<string, Closure(?string, SiteContext): mixed> */
    protected array $defaults = [];

    /** @var array<string, array{value: mixed, from: ?string}> */
    protected array $memo = [];

    public function store(string $resource, Closure $find, ?Closure $themeDefault = null): void
    {
        $this->stores[$resource] = $find;

        if ($themeDefault) {
            $this->defaults[$resource] = $themeDefault;
        }

        $this->flush();
    }

    public function has(string $resource): bool
    {
        return isset($this->stores[$resource]);
    }

    /** The item that applies here (or the theme default / null). */
    public function resolve(string $resource, ?string $location, SiteContext $context): mixed
    {
        return $this->lookup($resource, $location, $context)['value'];
    }

    /**
     * Where the item came from: own key, an ancestor key, "site:0", or null (theme default).
     * Used by the admin "Inherited from Engineering Faculty" indicator (FR-MENU-08).
     */
    public function source(string $resource, ?string $location, SiteContext $context): ?string
    {
        return $this->lookup($resource, $location, $context)['from'];
    }

    /** @return list<string> owners to try, nearest first */
    public function chain(string $resource, SiteContext $context): array
    {
        if (! $context->isMicrosite()) {
            return [RelationEngine::SITE_KEY];
        }

        $mode = $context->settings()?->modeFor($resource) ?? 'inherit';
        $chain = $context->chain();

        return match ($mode) {
            'own' => [$context->key()],
            'main' => [$context->key(), RelationEngine::SITE_KEY],
            default => $chain,
        };
    }

    /** Owner data changed (menu saved, hierarchy changed ...). */
    public function flush(): void
    {
        $this->memo = [];
    }

    /** @return array{value: mixed, from: ?string} */
    protected function lookup(string $resource, ?string $location, SiteContext $context): array
    {
        $find = $this->stores[$resource] ?? throw new InvalidArgumentException("No fallback store for \"{$resource}\".");
        $memoKey = "{$resource}|{$location}|{$context->key()}";

        if (isset($this->memo[$memoKey])) {
            return $this->memo[$memoKey];
        }

        foreach ($this->chain($resource, $context) as $owner) {
            [$type, $id] = explode(':', $owner, 2);

            if (($found = $find($type, (int) $id, $location)) !== null) {
                return $this->memo[$memoKey] = ['value' => $found, 'from' => $owner];
            }
        }

        $default = isset($this->defaults[$resource]) ? ($this->defaults[$resource])($location, $context) : null;

        return $this->memo[$memoKey] = ['value' => $default, 'from' => null];
    }
}
