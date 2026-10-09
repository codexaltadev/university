<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;

class ModuleInstallCommand extends Command
{
    protected $signature = 'uwms:module:install {path : Module ZIP file or module folder}';

    protected $description = 'Install a module from a ZIP or a folder (it starts inactive)';

    public function handle(ModuleManager $manager): int
    {
        $path = $this->argument('path');
        $path = str_starts_with($path, '/') ? $path : base_path($path);

        try {
            $module = str_ends_with(strtolower($path), '.zip')
                ? $manager->installZip($path)
                : $manager->install($path);
        } catch (ModuleOperationFailed $e) {
            return ModuleCommandOutput::failed($this, $e);
        }

        $this->components->info("Installed {$module->slug} {$module->version} (inactive). Activate with: php artisan uwms:module:activate {$module->slug}");

        return self::SUCCESS;
    }
}
