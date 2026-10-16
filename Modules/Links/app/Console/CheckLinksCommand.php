<?php

namespace Modules\Links\Console;

use Illuminate\Console\Command;
use Modules\Links\Support\LinkChecker;

/** FR-LINK-03: weekly (Monday 04:00) — asks every outside link for an answer, reports newly broken ones. */
class CheckLinksCommand extends Command
{
    protected $signature = 'links:check';

    protected $description = 'Check the important links and report broken ones';

    public function handle(LinkChecker $checker): int
    {
        $result = $checker->run();
        $this->components->info("Checked {$result['checked']} links: {$result['broken']} broken ({$result['newly']} new).");

        return self::SUCCESS;
    }
}
