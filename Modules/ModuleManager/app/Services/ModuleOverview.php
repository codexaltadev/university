<?php

namespace Modules\ModuleManager\Services;

use Illuminate\Support\Facades\File;
use Platform\Models\CoreModule;
use Platform\Modules\Manifest;
use Platform\Modules\ModulePermissions;
use Platform\Modules\ModuleRegistry;

/**
 * Read-only facts about a module for the Module Manager screen (FR-MODM-12).
 */
class ModuleOverview
{
    public function __construct(protected ModuleRegistry $modules, protected ModulePermissions $permissions) {}

    public function manifest(CoreModule $module): ?Manifest
    {
        return $this->modules->manifest($module->slug);
    }

    /** @return array<string, string> slug => "constraint · status" */
    public function dependsOn(CoreModule $module): array
    {
        $result = [];

        foreach ($this->manifest($module)?->dependencies() ?? [] as $slug => $constraint) {
            $result[$slug] = $constraint.' · '.($this->modules->isActive($slug) ? __('modulemanager::manager.status.active') : __('modulemanager::manager.status.inactive'));
        }

        return $result;
    }

    /** @return list<string> slugs of modules that need this one */
    public function usedBy(CoreModule $module): array
    {
        return collect($this->modules->manifests())
            ->filter(fn (Manifest $other): bool => array_key_exists($module->slug, $other->dependencies()))
            ->map(fn (Manifest $other): string => $other->slug())
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function permissions(CoreModule $module): array
    {
        $manifest = $this->manifest($module);

        return $manifest
            ? [...$this->permissions->keysForDirectory($manifest->path, $manifest->name()), ...$manifest->customPermissions()]
            : [];
    }

    public function readme(CoreModule $module): string
    {
        $path = ($this->manifest($module)?->path ?? '').'/README.md';

        return File::exists($path) ? (string) File::get($path) : '';
    }
}
