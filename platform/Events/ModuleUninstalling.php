<?php

namespace Platform\Events;

use Platform\Modules\Manifest;

/**
 * Before uninstall: listeners remove this module's rows from kernel tables
 * (relations, assignments, media usage, taxonomy, highlights, search index).
 */
class ModuleUninstalling extends ModuleEvent
{
    public function __construct(Manifest $manifest, public readonly bool $deleteData)
    {
        parent::__construct($manifest);
    }
}
