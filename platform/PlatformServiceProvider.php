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
use Platform\Localization\DatabaseTranslationLoader;
use Platform\Localization\Languages;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaUsages;
use Platform\Models\BaseModel;
use Platform\Modules\ModuleAutoloader;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Modules\ModuleRegistry;
use Platform\Permissions\EntityPermissions;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registry;
use Platform\Relations\MainSiteQueue;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Scope\ScopeResolver;
use Platform\Settings\Settings;
use Platform\Statistics\Statistics;
use Platform\Support\BlueprintMacros;
use Platform\Support\MorphMap;
use Platform\Taxonomy\Vocabularies;

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
            FallbackResolver::class, MicrositeResolver::class, Languages::class, Settings::class,
            Vocabularies::class, MediaLibrary::class, MediaUsages::class, Targets::class, MainSiteQueue::class, Statistics::class,
        ] as $service) {
            $this->app->singleton($service);
        }

        BlueprintMacros::register();

        // lang/ files plus the admin's interface-string overrides (FR-LANG-03)
        $this->app->extend('translation.loader', fn ($files) => new DatabaseTranslationLoader($files));

        // ZIP-installed modules, and bundled ones not yet in composer's autoload
        // (a fresh module:make-uwms must work without "composer dump-autoload")
        $autoloader = $this->app->make(ModuleAutoloader::class);
        $autoloader->registerRuntimeModules(config('platform.modules.runtime_path'));
        $autoloader->registerRuntimeModules(base_path('Modules'));
    }

    public function boot(): void
    {
        // Shared admin components' views (platform::…)
        $this->loadViewsFrom(base_path('platform/resources/views'), 'platform');
        $this->loadTranslationsFrom(base_path('platform/resources/lang'), 'platform');

        // Missing translations fall back to the default website language (FR-LANG-05)
        $this->app->make(Languages::class)->applyFallback();

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
                Commands\CleanActivityLogCommand::class,
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
            $this->app->make(Vocabularies::class)->flush();
        };

        Event::listen([ModuleActivated::class, ModuleDeactivated::class], $refresh);

        // FR-STAT-05: published / changed / deleted content → counters counted again
        Event::listen(['eloquent.saved: *', 'eloquent.deleted: *', 'eloquent.restored: *'], function (string $event, array $models): void {
            if (($models[0] ?? null) instanceof BaseModel) {
                Statistics::flush();
            }
        });

        // Uninstall with data: remove the module's rows from kernel tables (SAD Part A §10.4)
        Event::listen(function (ModuleUninstalling $event): void {
            if (! $event->deleteData) {
                return;
            }

            $types = array_keys($event->manifest->morphAliases());
            $this->app->make(RelationEngine::class)->forgetTypes($types);
            $this->app->make(AssignmentEngine::class)->forgetTypes($types);
            $this->app->make(Settings::class)->forgetGroup($event->manifest->slug());
            $this->app->make(Vocabularies::class)->forgetModule($event->manifest->slug(), $types);
            $this->app->make(MediaUsages::class)->forgetTypes($types);
        });
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('uwms:highlights:expire')->hourly();           // SAD Part B §3.5
            $schedule->command('uwms:activity:clean')->dailyAt('03:00');      // FR-LOG-07
        });
    }
}
