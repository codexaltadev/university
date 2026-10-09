<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Search\SearchIndex;

class SearchRebuildCommand extends Command
{
    protected $signature = 'uwms:search:rebuild {module? : Only this module (slug)}';

    protected $description = 'Build the website search index again from the visible records';

    public function handle(SearchIndex $index): int
    {
        $count = $index->rebuild($this->argument('module'));
        $this->components->info("Indexed {$count} records.");

        return self::SUCCESS;
    }
}
