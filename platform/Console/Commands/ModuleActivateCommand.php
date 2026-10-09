<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;

class ModuleActivateCommand extends Command
{
    protected $signature = 'uwms:module:activate {slug}';

    protected $description = 'Activate an installed module';

    public function handle(ModuleManager $manager): int
    {
        try {
            $manager->activate($this->argument('slug'));
        } catch (ModuleOperationFailed $e) {
            return ModuleCommandOutput::failed($this, $e);
        }

        $this->components->info("Activated {$this->argument('slug')}.");

        return self::SUCCESS;
    }
}
