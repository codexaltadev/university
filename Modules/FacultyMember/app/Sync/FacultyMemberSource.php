<?php

namespace Modules\FacultyMember\Sync;

/**
 * Where the teachers come from (DB-03 §8.3): the faculty site's API, or a JSON file of the
 * same shape. A new source only has to return the raw records; RecordMapper reads them.
 */
interface FacultyMemberSource
{
    /**
     * @return list<array<string, mixed>> raw records
     *
     * @throws SourceUnavailable
     */
    public function records(): array;

    /** For the sync report, e.g. "JSON file faculty-members.json". */
    public function describe(): string;
}
