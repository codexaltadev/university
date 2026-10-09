<?php

namespace Platform\Modules;

use Composer\Autoload\ClassLoader;

/**
 * PSR-4 autoloading for ZIP-installed modules, which are not part of the
 * project's composer.json (ADR-03: no composer run in production).
 * Bundled modules are autoloaded by composer (merge plugin).
 */
class ModuleAutoloader
{
    public function registerRuntimeModules(string $runtimePath): void
    {
        foreach (glob(rtrim($runtimePath, '/').'/*/module.json') ?: [] as $file) {
            $this->registerDirectory(dirname($file));
        }
    }

    public function registerDirectory(string $directory): void
    {
        $name = basename($directory);
        $namespace = config('modules.namespace', 'Modules')."\\{$name}\\";
        $loader = $this->loader();

        $loader->addPsr4($namespace, $directory.'/app/', true);
        $loader->addPsr4($namespace.'Database\\Factories\\', $directory.'/database/factories/', true);
        $loader->addPsr4($namespace.'Database\\Seeders\\', $directory.'/database/seeders/', true);
    }

    protected function loader(): ClassLoader
    {
        return array_values(ClassLoader::getRegisteredLoaders())[0];
    }
}
