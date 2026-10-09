<?php

namespace Platform\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Settings of one group were saved (SAD Part A §8): caches that use them are cleared. */
class SettingsUpdated
{
    use Dispatchable;

    /** @param list<string> $keys the keys whose value changed */
    public function __construct(public string $group, public array $keys) {}
}
