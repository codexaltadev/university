<?php

namespace Platform\Modules;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use Platform\Filament\Resources\Roles\RoleResource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Finder\Finder;

/**
 * Shield permissions per module (SAD Part A §7.4, "Module Lifecycle").
 * Works from the module's own files, so it does not depend on which
 * plugins the panel registered in the current request.
 */
class ModulePermissions
{
    /** Kernel / app resources that are not part of any module. */
    public const KERNEL_RESOURCES = [RoleResource::class];

    /**
     * Resource permissions of one module (on activate; POC-1).
     *
     * @return list<string> permission keys of the module
     */
    public function sync(string $module): array
    {
        $manifest = Manifest::fromDirectory(Module::getModulePath($module));
        $keys = $this->keysForDirectory($manifest->path, $manifest->name());

        $this->store($keys, $manifest->slug(), 'resource');

        return $keys;
    }

    /** manifest custom_permissions (on install). */
    public function syncCustom(Manifest $manifest): void
    {
        $this->store($manifest->customPermissions(), $manifest->slug(), 'custom');
    }

    /**
     * PublishMain / HighlightMain per attachable model (BRD Q-26, SAD Part A §7.4):
     * only holders decide what appears and is highlighted on the main site.
     *
     * @param  list<class-string>  $models
     */
    public function syncAttachable(?string $slug, array $models): void
    {
        $keys = [];
        foreach ($models as $model) {
            $keys[] = 'PublishMain:'.class_basename($model);
            $keys[] = 'HighlightMain:'.class_basename($model);
        }

        $this->store($keys, $slug, 'custom');
    }

    /** Permissions of kernel resources (Role editor ...). */
    public function syncKernel(): void
    {
        $this->store($this->keysForResources(self::KERNEL_RESOURCES), null, 'resource');
    }

    /** Uninstall with data: every permission the module owns (resource, custom, entity). */
    public function removeAll(Manifest $manifest): void
    {
        Permission::query()
            ->where('guard_name', $this->guard())
            ->where(fn ($q) => $q
                ->where('module_slug', $manifest->slug())
                ->orWhereIn('name', [...$this->keysForDirectory($manifest->path, $manifest->name()), ...$manifest->customPermissions()]))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<string> */
    public function keysFor(string $module): array
    {
        return $this->keysForDirectory(Module::getModulePath($module), $module);
    }

    /** @return list<string> */
    public function keysForDirectory(string $directory, string $name): array
    {
        return $this->keysForResources($this->resourcesIn($directory, $name));
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
        return $this->resourcesIn(Module::getModulePath($module), $module);
    }

    /** @return list<class-string> */
    public function resourcesIn(string $directory, string $name): array
    {
        $path = rtrim($directory, '/').'/app/Filament/Resources';

        if (! is_dir($path)) {
            return [];
        }

        $namespace = config('modules.namespace', 'Modules')."\\{$name}\\Filament\\Resources\\";
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

    /**
     * @param  list<class-string>  $resources
     * @return list<string>
     */
    protected function keysForResources(array $resources): array
    {
        $methods = config('filament-shield.policies.methods', []);

        return collect($resources)
            ->flatMap(fn (string $resource): array => collect(FilamentShield::getDefaultPermissionKeys($resource, $methods))
                ->pluck('key')
                ->all())
            ->unique()
            ->values()
            ->all();
    }

    /** @param list<string> $keys */
    protected function store(array $keys, ?string $slug, string $kind): void
    {
        foreach ($keys as $key) {
            Permission::findOrCreate($key, $this->guard())
                ->forceFill(['module_slug' => $slug, 'kind' => $kind])
                ->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function guard(): string
    {
        return config('auth.defaults.guard', 'web');
    }
}
