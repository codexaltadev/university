<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Modules\ModuleOperationFailed;

/** Prints the readable error list of a refused module operation (FR-MODM-04). */
final class ModuleCommandOutput
{
    public static function failed(Command $command, ModuleOperationFailed $e): int
    {
        $command->components->error(ucfirst($e->operation).' refused — nothing was changed:');

        foreach ($e->errors as $error) {
            $command->line("  • {$error}");
        }

        return Command::FAILURE;
    }
}
