<?php

namespace Platform\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Platform\Modules\Manifest;

/** Base of the module lifecycle events (SAD Part A §8). */
abstract class ModuleEvent
{
    use Dispatchable;

    public function __construct(public readonly Manifest $manifest) {}

    public function slug(): string
    {
        return $this->manifest->slug();
    }
}
