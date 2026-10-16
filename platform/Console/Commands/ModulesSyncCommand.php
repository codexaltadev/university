<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;
use Platform\Assignments\RoleCatalog;
use Platform\Modules\Manifest;
use Platform\Modules\ModuleAssets;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;

class ModulesSyncCommand extends Command
{
    protected $signature = 'uwms:modules:sync';

    protected $description = 'Record bundled modules; create module permissions and the role catalog; publish module assets';

    public function handle(ModuleManager $manager, ModulePermissions $permissions, Registry $registry, RoleCatalog $roles): int
    {
        $added = $manager->syncBundled();

        foreach (Module::allEnabled() as $module) {
            $permissions->sync($module->getName());
            $permissions->syncCustom($manifest = Manifest::fromDirectory($module->getPath()));   // e.g. Update:Settings
            app(ModuleAssets::class)->publish($manifest);                                           // public/ → public/modules/{slug}
        }

        foreach (collect($registry->all('attachable'))->groupBy('module') as $slug => $entries) {
            $permissions->syncAttachable($slug, $entries->pluck('model')->all());
        }

        $roles->sync();

        $this->components->info($added ? 'Recorded: '.implode(', ', $added) : 'All bundled modules were already recorded.');

        return self::SUCCESS;
    }
}
