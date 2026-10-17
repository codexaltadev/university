<?php

namespace Modules\FacultyMember\Console;

use Illuminate\Console\Command;
use Modules\FacultyMember\Sync\FacultyMemberSync;
use Modules\FacultyMember\Sync\SyncResult;

/** faculty-member:sync — teachers from the faculty site (FR-FM-08); daily at 05:30 when Settings › Faculty Members › Daily sync is on. */
class SyncFacultyMembersCommand extends Command
{
    protected $signature = 'faculty-member:sync';

    protected $description = 'Copy the teachers from the faculty site (JSON file or API, Settings › Faculty Members)';

    public function handle(FacultyMemberSync $sync): int
    {
        $result = $sync->run();
        if (! $result->ok()) {
            $this->error($result->failure.' — '.__('facultymember::facultymember.sync.nothing_removed'));

            return self::FAILURE;
        }

        $this->info(__('facultymember::facultymember.sync.done', SyncResult::counts($result->toArray())));
        if ($result->unmatchedDepartments !== []) {
            $this->warn(__('facultymember::facultymember.sync.unmatched', ['list' => implode(', ', $result->unmatchedDepartments)]));
        }
        foreach (array_slice($result->errors, 0, 20) as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
