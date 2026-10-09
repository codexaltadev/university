<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;
use Platform\Assignments\RoleCatalog;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;

class ModulesSyncCommand extends Command
{
    protected $signature = 'uwms:modules:sync';

    protected $description = 'Record bundled modules; create kernel and module permissions and the role catalog';

    public function handle(ModuleManager $manager, ModulePermissions $permissions, Registry $registry, RoleCatalog $roles): int
    {
        $added = $manager->syncBundled();
        $permissions->syncKernel();

        foreach (Module::allEnabled() as $module) {
            $permissions->sync($module->getName());
        }

        foreach (collect($registry->all('attachable'))->groupBy('module') as $slug => $entries) {
            $permissions->syncAttachable($slug, $entries->pluck('model')->all());
        }

        $roles->sync();

        $this->components->info($added ? 'Recorded: '.implode(', ', $added) : 'All bundled modules were already recorded.');

        return self::SUCCESS;
    }
}
