<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Views\ViewCounter;

class FlushViewCountsCommand extends Command
{
    protected $signature = 'uwms:views:flush';

    protected $description = 'Write the page views gathered in the cache to the view counts (runs every five minutes)';

    public function handle(ViewCounter $views): int
    {
        $this->components->info($views->flush().' record(s) got views.');

        return self::SUCCESS;
    }
}
