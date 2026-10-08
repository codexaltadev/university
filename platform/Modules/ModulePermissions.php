<?php

namespace Platform\Modules;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Finder\Finder;

/**
 * Shield permissions per module (SAD Part A §7.4, "Module Lifecycle").
 * Works from the module's own resource classes, so it does not depend on
 * which plugins the panel registered in the current request.
 */
class ModulePermissions
{
    /**
     * Create the Shield permissions of one module only (POC-1).
     *
     * @return list<string> permission keys of the module
     */
    public function sync(string $module): array
    {
        $keys = $this->keysFor($module);

        foreach ($keys as $key) {
            Permission::findOrCreate($key, $this->guard());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $keys;
    }

    /** Uninstall with data: remove the module's permissions. */
    public function remove(string $module): void
    {
        Permission::query()
            ->whereIn('name', $this->keysFor($module))
            ->where('guard_name', $this->guard())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<string> */
    public function keysFor(string $module): array
    {
        $methods = config('filament-shield.policies.methods', []);

        return collect($this->resourcesOf($module))
            ->flatMap(fn (string $resource): array => collect(FilamentShield::getDefaultPermissionKeys($resource, $methods))
                ->pluck('key')
                ->all())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Permissions that belong to installed but inactive modules, grouped by module.
     *
     * @return array<string, list<string>>
     */
    public function inactiveModulePermissions(): array
    {
        return collect(Module::allDisabled())
            ->mapWithKeys(fn ($module): array => [$module->getName() => $this->keysFor($module->getName())])
            ->filter()
            ->all();
    }

    /** @return list<class-string> Filament resource classes of the module (v5 layout). */
    public function resourcesOf(string $module): array
    {
        $path = Module::getModulePath($module).'app/Filament/Resources';

        if (! is_dir($path)) {
            return [];
        }

        $namespace = config('modules.namespace', 'Modules')."\\{$module}\\Filament\\Resources\\";
        $classes = [];

        foreach (Finder::create()->files()->in($path)->name('*Resource.php') as $file) {
            $class = $namespace.Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\');

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    protected function guard(): string
    {
        return config('auth.defaults.guard', 'web');
    }
}
