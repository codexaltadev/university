<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Relations\RelationEngine;

class ExpireHighlightsCommand extends Command
{
    protected $signature = 'uwms:highlights:expire';

    protected $description = 'Switch off highlights whose end date has passed (runs hourly)';

    public function handle(RelationEngine $relations): int
    {
        $this->components->info($relations->expireHighlights().' highlight(s) expired.');

        return self::SUCCESS;
    }
}
