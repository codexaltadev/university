<?php

namespace Platform\Modules;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;
use Platform\Models\CoreModule;
use Throwable;

/**
 * Module status in core_modules instead of modules_statuses.json (ADR-02).
 *
 * No row yet: a bundled module (in Modules/) follows its manifest's
 * uwms.default_active (FR-MODM-16); anything else is inactive. This keeps
 * a fresh install, a deploy and the test suite working before any row exists.
 */
class DatabaseActivator implements ActivatorInterface
{
    /** @var array<string, string>|null name => status */
    protected ?array $statuses = null;

    public function __construct(protected Container $app) {}

    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;

        return $this->isActive($name) === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $status = $active ? CoreModule::STATUS_ACTIVE : CoreModule::STATUS_INSTALLED;
        $row = CoreModule::query()->where('name', $name)->first();

        if ($row) {
            $row->update(['status' => $status, 'activated_at' => $active ? now() : $row->activated_at]);
        } elseif ($manifest = $this->manifestFor($name)) {
            CoreModule::create([
                ...self::attributesFromManifest($manifest),
                'status' => $status,
                'installed_at' => now(),
                'activated_at' => $active ? now() : null,
                'installed_by' => Auth::id(),
            ]);
        }

        $this->statuses = null;
    }

    public function delete(Module $module): void
    {
        CoreModule::query()->where('name', $module->getName())->delete();
        $this->statuses = null;
    }

    public function reset(): void
    {
        CoreModule::query()->delete();
        $this->statuses = null;
    }

    public function forget(): void
    {
        $this->statuses = null;
    }

    public function isActive(string $name): bool
    {
        $statuses = $this->statuses();

        if (isset($statuses[$name])) {
            return $statuses[$name] === CoreModule::STATUS_ACTIVE;
        }

        $manifest = $this->manifestFor($name);

        return $manifest !== null
            && self::isBundledPath($manifest->path)
            && $manifest->defaultActive();
    }

    /** @return array<string, mixed> */
    public static function attributesFromManifest(Manifest $manifest): array
    {
        return [
            'slug' => $manifest->slug(),
            'name' => $manifest->name(),
            'type' => $manifest->type() ?: 'installable',
            'version' => $manifest->version() ?: '0.0.0',
            'is_bundled' => self::isBundledPath($manifest->path),
            'source_path' => $manifest->path,
            'manifest' => $manifest->data,
        ];
    }

    public static function isBundledPath(string $path): bool
    {
        return str_starts_with(realpath($path) ?: $path, realpath(base_path('Modules')) ?: base_path('Modules'));
    }

    /** @return array<string, string> */
    protected function statuses(): array
    {
        if ($this->statuses !== null) {
            return $this->statuses;
        }

        try {
            return $this->statuses = CoreModule::query()->pluck('status', 'name')->all();
        } catch (Throwable) {
            // Database not ready (fresh install, package discovery): use manifest defaults
            return [];
        }
    }

    protected function manifestFor(string $name): ?Manifest
    {
        foreach ([base_path('Modules'), config('platform.modules.runtime_path')] as $root) {
            if (is_file("{$root}/{$name}/module.json")) {
                try {
                    return Manifest::fromDirectory("{$root}/{$name}");
                } catch (Throwable) {
                    return null;
                }
            }
        }

        return null;
    }
}
