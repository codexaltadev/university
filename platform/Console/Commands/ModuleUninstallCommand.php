<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;

class ModuleUninstallCommand extends Command
{
    protected $signature = 'uwms:module:uninstall {slug} {--delete-data : Also drop the module\'s tables and permissions}';

    protected $description = 'Uninstall an inactive module (keeps its data unless --delete-data)';

    public function handle(ModuleManager $manager): int
    {
        $slug = $this->argument('slug');
        $deleteData = (bool) $this->option('delete-data');

        // FR-MODM-11: deleting data needs the slug typed again
        if ($deleteData && $this->input->isInteractive() && $this->ask("Type \"{$slug}\" to delete its data permanently") !== $slug) {
            $this->components->warn('Not confirmed; nothing was changed.');

            return self::FAILURE;
        }

        try {
            $manager->uninstall($slug, $deleteData);
        } catch (ModuleOperationFailed $e) {
            return ModuleCommandOutput::failed($this, $e);
        }

        $this->components->info("Uninstalled {$slug}".($deleteData ? ' and deleted its data.' : '; its data was kept.'));

        return self::SUCCESS;
    }
}
