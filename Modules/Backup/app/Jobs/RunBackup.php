<?php

namespace Modules\Backup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Backup\Support\BackupRunner;

/** "Backup now" and scheduled backups run on the "maintenance" queue (SAD Part B §10). */
class RunBackup implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public string $type = 'database', public ?int $userId = null)
    {
        $this->onQueue('maintenance');
    }

    public function handle(BackupRunner $runner): void
    {
        $runner->run($this->type, $this->userId);
    }
}
