<?php

namespace Platform\Modules;

use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as NwidartModule;
use Throwable;

/**
 * Lookup of modules by slug (uwms.slug) or class, and their active state.
 */
class ModuleRegistry
{
    /** @var array<string, string|null> class => slug */
    protected array $slugs = [];

    /** @var array<string, Manifest|null> name => manifest */
    protected array $manifests = [];

    public function slugForClass(string $class): ?string
    {
        if (array_key_exists($class, $this->slugs)) {
            return $this->slugs[$class];
        }

        $prefix = config('modules.namespace', 'Modules').'\\';

        if (! Str::startsWith($class, $prefix)) {
            return $this->slugs[$class] = null;
        }

        $name = Str::before(Str::after($class, $prefix), '\\');

        return $this->slugs[$class] = $this->manifestByName($name)?->slug();
    }

    public function isActive(string $slug): bool
    {
        return (bool) $this->find($slug)?->isEnabled();
    }

    public function find(string $slug): ?NwidartModule
    {
        foreach (Module::all() as $module) {
            if ($this->manifestByName($module->getName())?->slug() === $slug) {
                return $module;
            }
        }

        return null;
    }

    public function manifest(string $slug): ?Manifest
    {
        $module = $this->find($slug);

        return $module ? $this->manifestByName($module->getName()) : null;
    }

    public function manifestByName(string $name): ?Manifest
    {
        if (array_key_exists($name, $this->manifests)) {
            return $this->manifests[$name];
        }

        $module = Module::find($name);

        try {
            return $this->manifests[$name] = $module ? Manifest::fromDirectory($module->getPath()) : null;
        } catch (Throwable) {
            return $this->manifests[$name] = null;
        }
    }

    /** @return list<Manifest> every module on disk */
    public function manifests(): array
    {
        return collect(Module::all())
            ->map(fn (NwidartModule $module): ?Manifest => $this->manifestByName($module->getName()))
            ->filter()
            ->values()
            ->all();
    }

    /** Forget cached module lists after install / update / uninstall / status change. */
    public function refresh(): void
    {
        $this->slugs = [];
        $this->manifests = [];

        // nwidart keeps the scanned module list in a static property
        app(RepositoryInterface::class)->resetModules();

        $activator = app(ActivatorInterface::class);
        if ($activator instanceof DatabaseActivator) {
            $activator->forget();
        }
    }
}
