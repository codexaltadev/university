<?php

namespace Platform\Theme;

use Closure;

/**
 * Website data the kernel's theme components need from core modules, without the
 * kernel knowing their classes (R-1): the menu at a location, a header / footer
 * region, a homepage. The owning module provides it while it is active.
 *
 *   app(SiteData::class)->provide('menu', fn (string $location, SiteContext $c) => …);
 *   app(SiteData::class)->get('menu', 'primary', $context);   // null when nobody provides it
 */
class SiteData
{
    /** @var array<string, Closure> */
    protected array $providers = [];

    public function provide(string $key, Closure $provider): void
    {
        $this->providers[$key] = $provider;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function get(string $key, mixed ...$arguments): mixed
    {
        return isset($this->providers[$key]) ? ($this->providers[$key])(...$arguments) : null;
    }
}
