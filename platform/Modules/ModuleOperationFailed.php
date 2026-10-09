<?php

namespace Platform\Modules;

use RuntimeException;

/**
 * A module operation was refused or failed. $errors is the readable list
 * shown to the Super Admin (FR-MODM-04); nothing was left half-done.
 */
class ModuleOperationFailed extends RuntimeException
{
    /** @param list<string> $errors */
    public function __construct(public readonly string $operation, public readonly array $errors)
    {
        parent::__construct(ucfirst($operation).' failed: '.implode(' | ', $errors));
    }
}
