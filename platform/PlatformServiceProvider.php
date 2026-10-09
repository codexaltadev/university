<?php

namespace Platform;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Console\Commands;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeResolver;
use Platform\Context\MicrositeSetting;
use Platform\Events\ModuleActivated;
use Platform\Events\ModuleDeactivated;
use Platform\Events\ModuleUninstalling;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Modules\ModuleAutoloader;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Modules\ModuleRegistry;
use Platform\Permissions\EntityPermissions;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;
use Platform\Scope\ScopeResolver;
use Platform\Support\BlueprintMacros;
use Platform\Support\MorphMap;

/**
 * Platform kernel. Registered before nwidart's provider (bootstrap/providers.php),
 * so ZIP-installed modules are autoloadable when nwidart registers them.
 */
class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            ModuleRegistry::class, ModuleAutoloader::class, ModuleManager::class, ModulePermissions::class,
            Registry::class, ExtensionLoader::class, ScopeResolver::class, EntityPermissions::class,
            EntityHierarchy::class, RelationEngine::class, AssignmentEngine::class, RoleCatalog::class,
            FallbackResolver::class, MicrositeResolver::class,
        ] as $service) {
            $this->app->singleton($service);
        }

        BlueprintMacros::register();

        $this->app->make(ModuleAutoloader::class)->registerRuntimeModules(config('platform.modules.runtime_path'));
    }

    public function boot(): void
    {
        // Polymorphic columns hold aliases, never class names (ADR-17)
        MorphMap::register($this->app->make(ModuleRegistry::class));

        $this->app->make(ExtensionLoader::class)->load();

        // Engines read what active modules registered
        foreach ($this->app->make(Registry::class)->all('dynamicPermissions') as $entry) {
            $this->app->make(EntityPermissions::class)->register($entry['model']);
        }

        $this->app->make(EntityHierarchy::class)->watch();
        $this->registerFallbackStores();
        $this->registerListeners();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MakeModuleCommand::class,
                Commands\ModuleInstallCommand::class,
                Commands\ModuleActivateCommand::class,
                Commands\ModuleDeactivateCommand::class,
                Commands\ModuleUninstallCommand::class,
                Commands\ModulesSyncCommand::class,
                Commands\ExpireHighlightsCommand::class,
            ]);
        }
    }

    /**
     * The kernel owns the microsite template (core_microsite_settings.template);
     * menu, header, footer, homepage, stats, logo and banner stores come from the
     * core modules that own those tables.
     */
    protected function registerFallbackStores(): void
    {
        $this->app->make(FallbackResolver::class)->store('template', fn (string $type, int $id): ?string => $type === RelationEngine::SITE_TYPE
            ? null   // the main site's template is a theme setting (C-06)
            : MicrositeSetting::query()->where('owner_type', $type)->where('owner_id', $id)->value('template'));
    }

    protected function registerListeners(): void
    {
        $refresh = function (): void {
            $this->app->make(EntityHierarchy::class)->flush();
            $this->app->make(ScopeResolver::class)->forget();
            $this->app->make(FallbackResolver::class)->flush();
        };

        Event::listen([ModuleActivated::class, ModuleDeactivated::class], $refresh);

        // Uninstall with data: remove the module's rows from kernel tables (SAD Part A §10.4)
        Event::listen(function (ModuleUninstalling $event): void {
            if (! $event->deleteData) {
                return;
            }

            $types = array_keys($event->manifest->morphAliases());
            $this->app->make(RelationEngine::class)->forgetTypes($types);
            $this->app->make(AssignmentEngine::class)->forgetTypes($types);
        });
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command('uwms:highlights:expire')->hourly();   // SAD Part B §3.5
        });
    }
}
