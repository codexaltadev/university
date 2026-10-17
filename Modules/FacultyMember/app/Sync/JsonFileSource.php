<?php

namespace Modules\FacultyMember\Sync;

/**
 * Teachers from a JSON file: {"data": [...]} or a plain list. Until the faculty site's API is
 * available this reads the sample file shipped with the module (resources/data).
 */
class JsonFileSource implements FacultyMemberSource
{
    public function __construct(protected string $path) {}

    public static function samplePath(): string
    {
        return module_path('FacultyMember', 'resources/data/faculty-members.json');
    }

    public function records(): array
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new SourceUnavailable(__('facultymember::facultymember.sync.no_file', ['path' => $this->path]));
        }

        return RecordList::from(json_decode((string) file_get_contents($this->path), true));
    }

    public function describe(): string
    {
        return 'JSON '.basename($this->path);
    }
}
