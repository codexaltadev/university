<?php

namespace Platform\Search;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Brings one record's index rows up to date (FR-SRCH-09, SAD Part B §10 queue
 * "search"). Runs after the transaction commits; looks the record up again, so a
 * deleted or hidden record simply leaves the index.
 */
class IndexSearchDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $type, public int|string $id)
    {
        $this->onQueue('search');
        $this->afterCommit();
    }

    public function handle(SearchIndex $index): void
    {
        $index->refresh($this->type, $this->id);
    }
}
