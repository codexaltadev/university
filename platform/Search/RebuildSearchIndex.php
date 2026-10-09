<?php

namespace Platform\Search;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** A module was activated: its visible records go back into the website search. */
class RebuildSearchIndex implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public ?string $module = null)
    {
        $this->onQueue('search');
        $this->afterCommit();
    }

    public function handle(SearchIndex $index): void
    {
        $index->rebuild($this->module);
    }
}
