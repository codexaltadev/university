<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;

class ModuleDeactivateCommand extends Command
{
    protected $signature = 'uwms:module:deactivate {slug}';

    protected $description = 'Deactivate a module (its data, permissions and settings are kept)';

    public function handle(ModuleManager $manager): int
    {
        try {
            $manager->deactivate($this->argument('slug'));
        } catch (ModuleOperationFailed $e) {
            return ModuleCommandOutput::failed($this, $e);
        }

        $this->components->info("Deactivated {$this->argument('slug')}.");

        return self::SUCCESS;
    }
}
