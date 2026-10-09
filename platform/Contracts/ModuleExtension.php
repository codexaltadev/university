<?php

namespace Platform\Contracts;

use Platform\Registry\Registrar;

/**
 * A module's single registration entry point: Modules\{Name}\{Name}Extension
 * (SAD Part A §7.1). Runs only while the module is active.
 */
interface ModuleExtension
{
    public function register(Registrar $r): void;
}
